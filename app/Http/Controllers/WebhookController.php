<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\JoytelLog;
use App\Services\RSPService;
use App\Jobs\RedeemCouponJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookController extends Controller
{
    private RSPService $rspService;

    public function __construct(RSPService $rspService)
    {
        $this->rspService = $rspService;
    }

    /**
     * Handle SN/PIN webhook from warehouse system
     */
    public function handleSnPinCallback(Request $request): JsonResponse
    {
        try {
            Log::info("Received SN/PIN webhook", [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Extract data from request
            $orderTid = $request->input('order_tid') ?? $request->input('orderTid');
            $snPin = $request->input('sn_pin') ?? $request->input('snPin');
            $orderCode = $request->input('order_code') ?? $request->input('orderCode');

            if (!$orderTid) {
                Log::warning("Missing order TID in SN/PIN webhook", [
                    'payload' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Missing order TID'
                ], 400);
            }

            // Check for idempotency
            $cacheKey = "snpin_webhook_{$orderTid}";
            if (Cache::has($cacheKey)) {
                Log::info("Duplicate SN/PIN webhook detected, ignoring", [
                    'order_tid' => $orderTid
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Already processed'
                ]);
            }

            DB::beginTransaction();

            // Find order
            $order = Order::where('order_tid', $orderTid)->first();
            
            if (!$order) {
                Log::warning("Order not found for SN/PIN webhook", [
                    'order_tid' => $orderTid
                ]);
                
                DB::rollBack();
                return response()->json([
                    'success' => false,  
                    'message' => 'Order not found'
                ], 404);
            }

            // Update order with SN/PIN
            if ($snPin) {
                $order->setSnPin($snPin);
                Log::info("SN/PIN updated for order", [
                    'order_tid' => $orderTid,
                    'has_sn_pin' => !empty($snPin)
                ]);
            }

            if ($orderCode) {
                $order->order_code = $orderCode;
            }

            // Update response data
            $responseData = $order->response_data ?? [];
            $responseData['sn_pin_callback'] = $request->all();
            $responseData['sn_pin_received_at'] = new \DateTime();
            $order->response_data = $responseData;
            $order->save();

            // If this is an eSIM order and we have SN/PIN, trigger coupon redemption
            if ($snPin && !$order->isCompleted() && strpos($order->product_code, 'esim') !== false) {
                Log::info("Dispatching coupon redemption job", [
                    'order_tid' => $orderTid,
                    'sn_pin' => $snPin
                ]);
                
                // The SN/PIN might be the coupon code for RSP system
                RedeemCouponJob::dispatch($orderTid, $snPin, [
                    'sn_pin_callback' => $request->all()
                ])->onQueue('joytel');
            }

            // Set cache to prevent duplicate processing
            Cache::put($cacheKey, true, now()->addHours(24));

            DB::commit();

            Log::info("SN/PIN webhook processed successfully", [
                'order_tid' => $orderTid
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SN/PIN processed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error processing SN/PIN webhook", [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle QR code callback webhook
     */
    public function handleQrCodeCallback(Request $request): JsonResponse
    {
        try {
            Log::info("Received QR code webhook", [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $orderTid = $request->input('order_tid') ?? $request->input('orderTid');
            $qrCode = $request->input('qrcode') ?? $request->input('qr_code') ?? $request->input('qrCode');

            if (!$orderTid) {
                Log::warning("Missing order TID in QR code webhook", [
                    'payload' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Missing order TID'
                ], 400);
            }

            // Check for idempotency
            $cacheKey = "qrcode_webhook_{$orderTid}";
            if (Cache::has($cacheKey)) {
                Log::info("Duplicate QR code webhook detected, ignoring", [
                    'order_tid' => $orderTid
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Already processed'
                ]);
            }

            DB::beginTransaction();

            // Find order
            $order = Order::where('order_tid', $orderTid)->first();
            
            if (!$order) {
                Log::warning("Order not found for QR code webhook", [
                    'order_tid' => $orderTid
                ]);
                
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Update order with QR code and mark as completed
            if ($qrCode) {
                $order->setQrCode($qrCode);
            }

            // Update response data
            $responseData = $order->response_data ?? [];
            $responseData['qr_code_callback'] = $request->all();
            $responseData['qr_code_received_at'] = new \DateTime();
            
            // Mark order as completed
            $order->markAsCompleted($responseData);

            // Set cache to prevent duplicate processing
            Cache::put($cacheKey, true, now()->addHours(24));

            DB::commit();

            Log::info("QR code webhook processed successfully", [
                'order_tid' => $orderTid,
                'has_qr_code' => !empty($qrCode)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR code processed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error processing QR code webhook", [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle coupon redeem result notification from RSP system
     */
    public function handleCouponRedeemNotification(Request $request): JsonResponse
    {
        try {
            Log::info("Received coupon redeem notification", [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate RSP signature
            if (!$this->rspService->validateWebhookSignature($request->headers->all(), $request->all())) {
                Log::warning("Invalid signature in coupon redeem notification", [
                    'headers' => $request->headers->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 403);
            }

            $transId = $request->header('TransId');
            $couponCode = $request->input('couponCode');
            $status = $request->input('status');
            $qrCode = $request->input('qrcode') ?? $request->input('qrCode');
            
            // Try to find order by coupon code (which might be the SN/PIN)
            $order = Order::where('sn_pin', $couponCode)->first();
            
            if (!$order) {
                Log::warning("Order not found for coupon redeem notification", [
                    'coupon_code' => $couponCode,
                    'trans_id' => $transId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check for idempotency
            $cacheKey = "coupon_redeem_webhook_{$order->order_tid}_{$transId}";
            if (Cache::has($cacheKey)) {
                Log::info("Duplicate coupon redeem webhook detected, ignoring", [
                    'order_tid' => $order->order_tid,
                    'trans_id' => $transId
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Already processed'
                ]);
            }

            DB::beginTransaction();

            // Update order with coupon redeem result
            $responseData = $order->response_data ?? [];
            $responseData['coupon_redeem_notification'] = $request->all();
            $responseData['coupon_redeem_received_at'] = new \DateTime();

            if ($status === 'success' || $status === 'completed') {
                if ($qrCode) {
                    $order->setQrCode($qrCode);
                }
                $order->markAsCompleted($responseData);
                
                Log::info("Order completed via coupon redeem notification", [
                    'order_tid' => $order->order_tid,
                    'coupon_code' => $couponCode
                ]);
            } else {
                $errorMessage = $request->input('errorMessage') ?? $request->input('error') ?? 'Coupon redemption failed';
                $order->markAsFailed($errorMessage, $responseData);
                
                Log::warning("Order failed via coupon redeem notification", [
                    'order_tid' => $order->order_tid,
                    'error' => $errorMessage
                ]);
            }

            // Set cache to prevent duplicate processing
            Cache::put($cacheKey, true, now()->addHours(24));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Coupon redeem notification processed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error processing coupon redeem notification", [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle eSIM installation event notification from RSP system
     */
    public function handleESIMProgressNotification(Request $request): JsonResponse
    {
        try {
            Log::info("Received eSIM progress notification", [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate RSP signature
            if (!$this->rspService->validateWebhookSignature($request->headers->all(), $request->all())) {
                Log::warning("Invalid signature in eSIM progress notification", [
                    'headers' => $request->headers->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 403);
            }

            $transId = $request->header('TransId');
            $iccid = $request->input('iccid');
            $status = $request->input('status');
            $event = $request->input('event');

            // Find order by ICCID or other identifying information
            // This might require different logic depending on how orders are tracked
            $order = Order::whereJsonContains('response_data->iccid', $iccid)
                         ->orWhereJsonContains('response_data->esim_data->iccid', $iccid)
                         ->first();

            if (!$order) {
                Log::info("Order not found for eSIM progress notification, logging anyway", [
                    'iccid' => $iccid,
                    'event' => $event,
                    'status' => $status
                ]);
            }

            // Check for idempotency
            $cacheKey = "esim_progress_webhook_{$iccid}_{$transId}";
            if (Cache::has($cacheKey)) {
                Log::info("Duplicate eSIM progress webhook detected, ignoring", [
                    'iccid' => $iccid,
                    'trans_id' => $transId
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Already processed'
                ]);
            }

            if ($order) {
                DB::beginTransaction();

                // Update order with eSIM progress data
                $responseData = $order->response_data ?? [];
                $responseData['esim_progress_notifications'] = $responseData['esim_progress_notifications'] ?? [];
                $responseData['esim_progress_notifications'][] = array_merge($request->all(), [
                    'received_at' => new \DateTime()
                ]);

                // Update status based on event
                if ($event === 'installation_completed' || $status === 'installed') {
                    $order->markAsCompleted($responseData);
                    Log::info("Order completed via eSIM progress notification", [
                        'order_tid' => $order->order_tid,
                        'event' => $event
                    ]);
                } else {
                    $order->response_data = $responseData;
                    $order->save();
                }

                DB::commit();
            }

            // Set cache to prevent duplicate processing
            Cache::put($cacheKey, true, now()->addHours(24));

            Log::info("eSIM progress notification processed", [
                'iccid' => $iccid,
                'event' => $event,
                'status' => $status,
                'order_found' => $order ? true : false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'eSIM progress notification processed successfully'
            ]);

        } catch (\Exception $e) {
            if (isset($order)) {
                DB::rollBack();
            }
            
            Log::error("Error processing eSIM progress notification", [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
