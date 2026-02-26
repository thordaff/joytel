<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\WarehouseService;
use App\Services\RSPService;
use App\Jobs\RedeemCouponJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    private WarehouseService $warehouseService;
    private RSPService $rspService;

    public function __construct(WarehouseService $warehouseService, RSPService $rspService)
    {
        $this->warehouseService = $warehouseService;
        $this->rspService = $rspService;
    }

    /**
     * Submit new eSIM order
     */
    public function submitOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string|max:50',
            'quantity' => 'integer|min:1|max:100',
            'receive_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'cid' => 'nullable|string|max:50',
            'system_type' => 'in:warehouse,rsp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generate order TID
            $orderTid = Order::generateOrderTid();
            $systemType = $request->input('system_type', 'warehouse');

            Log::info("Creating new order", [
                'order_tid' => $orderTid,
                'system_type' => $systemType,
                'product_code' => $request->input('product_code')
            ]);

            // Create order in database
            $order = Order::create([
                'order_tid' => $orderTid,
                'product_code' => $request->input('product_code'),
                'quantity' => $request->input('quantity', 1),
                'status' => Order::STATUS_PENDING,
                'cid' => $request->input('cid'),
                'system_type' => $systemType,
                'request_data' => $request->all(),
                'submitted_at' => now()
            ]);

            // Submit to appropriate system
            if ($systemType === 'warehouse') {
                $result = $this->warehouseService->submitESIMOrder([
                    'order_tid' => $orderTid,
                    'product_code' => $request->input('product_code'),
                    'quantity' => $request->input('quantity', 1),
                    'receive_name' => $request->input('receive_name'),
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email', ''),
                ]);

                if ($result['success']) {
                    $order->update([
                        'order_code' => $result['order_code'],
                        'status' => Order::STATUS_PROCESSING,
                        'response_data' => $result['data']
                    ]);

                    Log::info("Order submitted to warehouse successfully", [
                        'order_tid' => $orderTid,
                        'order_code' => $result['order_code']
                    ]);
                } else {
                    $order->markAsFailed($result['error']['error_message'], $result['error']);
                    
                    Log::error("Failed to submit order to warehouse", [
                        'order_tid' => $orderTid,
                        'error' => $result['error']
                    ]);
                }
            } else {
                // For RSP system, we might have different logic here
                // For now, just mark as processing
                $order->markAsProcessing();
            }

            DB::commit();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order submitted successfully',
                    'data' => [
                        'order_tid' => $orderTid,
                        'order_code' => $result['order_code'] ?? null,
                        'status' => $order->status,
                        'system_type' => $systemType
                    ]
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to submit order',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Exception during order submission", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => [
                    'error_code' => '999',
                    'error_message' => 'System exception: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Query order status
     */
    public function queryOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_tid' => 'required_without:order_code|string',
            'order_code' => 'required_without:order_tid|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find order
            $query = Order::query();
            
            if ($request->has('order_tid')) {
                $query->where('order_tid', $request->input('order_tid'));
            } elseif ($request->has('order_code')) {
                $query->where('order_code', $request->input('order_code'));
            }

            $order = $query->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // If order is not completed and system is warehouse, query latest status
            if (!$order->isCompleted() && $order->system_type === 'warehouse' && $order->order_code) {
                try {
                    $statusResult = $this->warehouseService->queryESIMOrder(
                        $order->order_code,
                        $order->order_tid
                    );

                    if ($statusResult['success']) {
                        // Update order with latest data
                        $responseData = array_merge($order->response_data ?? [], $statusResult['data']);
                        $order->response_data = $responseData;
                        
                        // Check if status changed
                        if (isset($statusResult['data']['status'])) {
                            // Map JoyTel status to our status
                            $joytelStatus = $statusResult['data']['status'];
                            if ($joytelStatus === 'completed' || $joytelStatus === 'success') {
                                $order->markAsCompleted($statusResult['data']);
                            }
                        }
                        
                        $order->save();
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to query order status from JoyTel", [
                        'order_tid' => $order->order_tid,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_tid' => $order->order_tid,
                    'order_code' => $order->order_code,
                    'product_code' => $order->product_code,
                    'quantity' => $order->quantity,
                    'status' => $order->status,
                    'sn_pin' => $order->sn_pin,
                    'qrcode' => $order->qrcode,
                    'cid' => $order->cid,
                    'system_type' => $order->system_type,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'submitted_at' => $order->submitted_at,
                    'completed_at' => $order->completed_at,
                    'response_data' => $order->response_data
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Exception during order query", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => [
                    'error_code' => '999',
                    'error_message' => 'System exception: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Submit OTA recharge order
     */
    public function submitOTARecharge(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string|max:50',
            'sn_pin' => 'required|string|max:100',
            'quantity' => 'integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $orderTid = Order::generateOrderTid();

            Log::info("Creating OTA recharge order", [
                'order_tid' => $orderTid,
                'product_code' => $request->input('product_code'),
                'sn_pin' => $request->input('sn_pin')
            ]);

            // Create order in database
            $order = Order::create([
                'order_tid' => $orderTid,
                'product_code' => $request->input('product_code'),
                'quantity' => $request->input('quantity', 1),
                'status' => Order::STATUS_PENDING,
                'sn_pin' => $request->input('sn_pin'),
                'system_type' => Order::SYSTEM_WAREHOUSE,
                'request_data' => $request->all(),
                'submitted_at' => now()
            ]);

            // Submit OTA recharge
            $result = $this->warehouseService->submitOTARecharge([
                'order_tid' => $orderTid,
                'product_code' => $request->input('product_code'),
                'quantity' => $request->input('quantity', 1),
                'sn_pin' => $request->input('sn_pin')
            ]);

            if ($result['success']) {
                $order->update([
                    'status' => Order::STATUS_PROCESSING,
                    'response_data' => $result['data']
                ]);

                Log::info("OTA recharge submitted successfully", [
                    'order_tid' => $orderTid
                ]);
            } else {
                $order->markAsFailed($result['error']['error_message'], $result['error']);
            }

            DB::commit();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTA recharge submitted successfully',
                    'data' => [
                        'order_tid' => $orderTid,
                        'status' => $order->status
                    ]
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to submit OTA recharge',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Exception during OTA recharge submission", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => [
                    'error_code' => '999',
                    'error_message' => 'System exception: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * List orders with pagination
     */
    public function listOrders(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');
            $systemType = $request->input('system_type');

            $query = Order::query()->orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            if ($systemType) {
                $query->where('system_type', $systemType);
            }

            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Exception during orders listing", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => [
                    'error_code' => '999',
                    'error_message' => 'System exception: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Store order from web interface (form submission)
     */
    public function storeFromWeb(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'system_type' => 'required|in:warehouse,rsp',
            'order_type' => 'required_if:system_type,warehouse|in:esim,ota',
            'product_code' => 'required|string|max:50',
            'quantity' => 'integer|min:1|max:100',
            'receive_name' => 'required_if:order_type,esim|string|max:100',
            'phone' => 'required_if:order_type,esim|string|max:20',
            'email' => 'nullable|email|max:100',
            'cid' => 'nullable|string|max:50',
            'sn_pin' => 'required_if:order_type,ota|string',
            'coupon_code' => 'required_if:system_type,rsp|string'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $systemType = $request->input('system_type');
            $orderType = $request->input('order_type', 'esim');

            if ($systemType === 'warehouse') {
                if ($orderType === 'ota') {
                    // Handle OTA recharge
                    $response = $this->submitOTARecharge($request);
                } else {
                    // Handle regular eSIM order
                    $response = $this->submitOrder($request);
                }
            } else {
                // Handle RSP system (coupon redemption)
                $response = $this->handleRSPOrder($request);
            }

            $responseData = $response->getData(true);
            
            if ($responseData['success']) {
                return redirect()->route('orders.show', $responseData['data']['order_tid'])
                    ->with('success', 'Order created successfully!');
            } else {
                return back()->withErrors(['error' => $responseData['message']])->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Web order creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['_token'])
            ]);

            return back()->withErrors(['error' => 'Failed to create order. Please try again.'])->withInput();
        }
    }

    /**
     * Handle RSP system order (coupon redemption)
     */
    private function handleRSPOrder(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create placeholder order for RSP system
            $orderTid = 'RSP' . time() . rand(1000, 9999);
            
            $order = Order::create([
                'order_tid' => $orderTid,
                'product_code' => $request->input('product_code'),
                'quantity' => $request->input('quantity', 1),
                'system_type' => 'rsp',
                'status' => Order::STATUS_PENDING,
                'request_data' => [
                    'coupon_code' => $request->input('coupon_code'),
                    'system_type' => 'rsp'
                ]
            ]);

            // Dispatch coupon redemption job
            if ($request->filled('coupon_code')) {
                RedeemCouponJob::dispatch($orderTid, $request->input('coupon_code'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RSP order created and queued for processing',
                'data' => [
                    'order_tid' => $orderTid,
                    'status' => 'pending',
                    'system_type' => 'rsp'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
