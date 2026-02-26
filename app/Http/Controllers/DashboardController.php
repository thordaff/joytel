<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\JoytelLog;
use App\Services\ProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display dashboard overview
     */
    public function index()
    {
        // Get order statistics
        $stats = [
            'total_orders' => Order::count(),
            'completed_orders' => Order::where('status', Order::STATUS_COMPLETED)->count(),
            'processing_orders' => Order::where('status', Order::STATUS_PROCESSING)->count(),
            'failed_orders' => Order::where('status', Order::STATUS_FAILED)->count(),
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
        ];

        // Get recent orders
        $recent_orders = Order::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get chart data (last 7 days)
        $chart_data = $this->getOrdersChartData();

        return view('dashboard', compact('stats', 'recent_orders', 'chart_data'));
    }

    /**
     * Display orders list
     */
    public function orders(Request $request)
    {
        $query = Order::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('system_type')) {
            $query->where('system_type', $request->system_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_tid', 'LIKE', "%{$search}%")
                  ->orWhere('order_code', 'LIKE', "%{$search}%")
                  ->orWhere('product_code', 'LIKE', "%{$search}%")
                  ->orWhere('cid', 'LIKE', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('orders.index', compact('orders'));
    }

    /**
     * Show order details
     */
    public function showOrder(Order $order)
    {
        return view('orders.show', compact('order'));
    }

    /**
     * Show create order form
     */
    public function createOrder()
    {
        return view('orders.create');
    }

    /**
     * Display queue monitor
     */
    public function queueMonitor()
    {
        // Get recent jobs from joytel_logs
        $recent_logs = JoytelLog::orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get queue statistics
        $queue_stats = [
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'total_api_calls_today' => JoytelLog::whereDate('created_at', today())->count(),
            'successful_calls_today' => JoytelLog::whereDate('created_at', today())
                ->where('response_code', '000')
                ->count(),
        ];

        return view('queue.monitor', compact('recent_logs', 'queue_stats'));
    }

    /**
     * Display API logs
     */
    public function apiLogs(Request $request)
    {
        $query = JoytelLog::query();

        // Apply filters
        if ($request->filled('system_type')) {
            $query->where('system_type', $request->system_type);
        }

        if ($request->filled('endpoint')) {
            $query->where('endpoint', 'LIKE', "%{$request->endpoint}%");
        }

        if ($request->filled('order_tid')) {
            $query->where('order_tid', $request->order_tid);
        }

        if ($request->filled('response_code')) {
            $query->where('response_code', $request->response_code);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('logs.index', compact('logs'));
    }

    /**
     * Get products by system type and category (API endpoint)
     */
    public function getProducts(Request $request)
    {
        $systemType = $request->get('system_type', 'warehouse');
        $category = $request->get('category');

        try {
            // Use ProductSyncService to get products from API with local fallback
            $productSyncService = new ProductSyncService();
            $products = $productSyncService->getProducts($systemType, $category);

            // Transform for frontend use
            $transformed = [];
            foreach ($products as $categoryKey => $categoryProducts) {
                $transformed[$categoryKey] = array_map(function ($product) {
                    return [
                        'value' => $product['product_code'],
                        'text' => $product['name'],
                        'price' => $product['price'] ?? null,
                        'description' => $product['description'] ?? '',
                        'region' => $product['region'] ?? null,
                        'data_amount' => $this->formatDataAmount($product['data_amount_mb'] ?? 0)
                    ];
                }, $categoryProducts);
            }

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'source' => 'API with local fallback'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching products', [
                'error' => $e->getMessage(),
                'system_type' => $systemType,
                'category' => $category
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'data' => []
            ], 500);
        }
    }

    /**
     * Format data amount from MB to readable string
     */
    private function formatDataAmount(int $dataMb): string
    {
        if ($dataMb >= 1024) {
            return round($dataMb / 1024, 1) . 'GB';
        }
        
        return $dataMb . 'MB';
    }

    /**
     * Get pending jobs count from jobs table
     */
    private function getPendingJobsCount(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get failed jobs count from failed_jobs table
     */
    private function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get orders chart data for the last 7 days
     */
    private function getOrdersChartData(): array
    {
        $days = [];
        $completed = [];
        $failed = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->format('M j');

            $completed[] = Order::whereDate('created_at', $date)
                ->where('status', Order::STATUS_COMPLETED)
                ->count();

            $failed[] = Order::whereDate('created_at', $date)
                ->where('status', Order::STATUS_FAILED)
                ->count();
        }

        return [
            'labels' => $days,
            'completed' => $completed,
            'failed' => $failed,
        ];
    }

    /**
     * AJAX search orders
     */
    public function searchOrders(Request $request)
    {
        $query = $request->get('q', '');
        
        $orders = Order::where('order_tid', 'LIKE', "%{$query}%")
            ->orWhere('order_code', 'LIKE', "%{$query}%")
            ->orWhere('product_code', 'LIKE', "%{$query}%")
            ->orWhere('cid', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['order_tid', 'product_code', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get live statistics for dashboard
     */
    public function liveStats()
    {
        $stats = [
            'total_orders' => Order::count(),
            'completed_orders' => Order::where('status', Order::STATUS_COMPLETED)->count(),
            'processing_orders' => Order::where('status', Order::STATUS_PROCESSING)->count(),
            'failed_orders' => Order::where('status', Order::STATUS_FAILED)->count(),
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'last_updated' => now()->format('H:i:s')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('view:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit order form
     */
    public function editOrder(Order $order)
    {
        return view('orders.edit', compact('order'));
    }

    /**
     * Update order
     */
    public function updateOrder(Request $request, Order $order)
    {
        $validator = \Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,completed,failed',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $order->update([
                'status' => $request->status,
                'notes' => $request->notes
            ]);

            return redirect()->route('orders.show', $order->order_tid)
                ->with('success', 'Order updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update order']);
        }
    }

    /**
     * Bulk action on orders
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $orderIds = $request->input('orders', []);

        if (!in_array($action, ['delete', 'retry', 'mark_completed']) || empty($orderIds)) {
            return back()->withErrors(['error' => 'Invalid bulk action']);
        }

        try {
            $orders = Order::whereIn('id', $orderIds);

            switch ($action) {
                case 'delete':
                    $orders->delete();
                    $message = 'Orders deleted successfully';
                    break;
                case 'retry':
                    $orders->update(['status' => Order::STATUS_PENDING]);
                    $message = 'Orders marked for retry';
                    break;
                case 'mark_completed':
                    $orders->update(['status' => Order::STATUS_COMPLETED]);
                    $message = 'Orders marked as completed';
                    break;
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Bulk action failed']);
        }
    }

    /**
     * Export orders
     */
    public function exportOrders($format)
    {
        if (!in_array($format, ['csv', 'excel', 'pdf'])) {
            abort(404);
        }

        // For now, just return CSV
        $orders = Order::orderBy('created_at', 'desc')->get();
        
        $filename = "orders_export_" . date('Y-m-d_H-i-s') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, ['Order TID', 'Product Code', 'Status', 'System Type', 'Created At']);
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_tid,
                    $order->product_code,
                    $order->status,
                    $order->system_type,
                    $order->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Order invoice/receipt
     */
    public function orderInvoice(Order $order)
    {
        return view('orders.invoice', compact('order'));
    }

    /**
     * Show single log entry
     */
    public function showLog(\App\Models\JoytelLog $log)
    {
        return view('logs.show', compact('log'));
    }

    /**
     * Delete log entry
     */
    public function deleteLog(\App\Models\JoytelLog $log)
    {
        try {
            $log->delete();
            return back()->with('success', 'Log entry deleted');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete log entry']);
        }
    }

    /**
     * Clear old logs
     */
    public function clearOldLogs(Request $request)
    {
        $days = $request->input('days', 30);
        
        try {
            $count = \App\Models\JoytelLog::where('created_at', '<', now()->subDays($days))->count();
            \App\Models\JoytelLog::where('created_at', '<', now()->subDays($days))->delete();
            
            return back()->with('success', "Deleted {$count} old log entries");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to clear old logs']);
        }
    }

    /**
     * Help page
     */
    public function help()
    {
        return view('help.index');
    }

    /**
     * API documentation
     */
    public function apiDocs()
    {
        return view('help.api-docs');
    }

    /**
     * Integration guide
     */
    public function integration()
    {
        return view('help.integration');
    }
}
