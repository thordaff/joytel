<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" and "inertia" middleware groups.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'joytel.role:dev,admin'])->group(function () {
    // Dashboard Routes
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.home');

    // API Routes for AJAX calls
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/products', [DashboardController::class, 'getProducts'])->name('products');
    });

    // AJAX Routes for live updates
    Route::prefix('ajax')->name('ajax.')->middleware(['web'])->group(function () {
        Route::get('/orders/search', [DashboardController::class, 'searchOrders'])->name('orders.search');
        Route::get('/stats/live', [DashboardController::class, 'liveStats'])->name('stats.live');
        Route::post('/cache/clear', [DashboardController::class, 'clearCache'])
            ->middleware('joytel.role:dev')
            ->name('cache.clear');
    });

    // Orders Management Routes (Web Interface)
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [DashboardController::class, 'orders'])->name('index');
        Route::get('/create', [DashboardController::class, 'createOrder'])->name('create');
        Route::get('/{order}', [DashboardController::class, 'showOrder'])->name('show');

        // AJAX endpoints for order management
        Route::post('/{order}/retry', function(\App\Models\Order $order) {
            // Simple retry logic - you can expand this
            try {
                if ($order->status === 'failed' || $order->status === 'pending') {
                    $order->status = 'pending';
                    $order->save();

                    // Could dispatch job here to retry
                    // RedeemCouponJob::dispatch($order->order_tid, $order->sn_pin);

                    return response()->json(['success' => true, 'message' => 'Order retry initiated']);
                }

                return response()->json(['success' => false, 'message' => 'Order cannot be retried']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }
        })->name('retry');

        Route::post('/{order}/query-status', function(\App\Models\Order $order) {
            // Query latest status from API
            try {
                if ($order->system_type === 'warehouse' && $order->order_code) {
                    $warehouseService = new \App\Services\WarehouseService();
                    $result = $warehouseService->queryESIMOrder($order->order_code, $order->order_tid);

                    if ($result['success']) {
                        $order->response_data = array_merge($order->response_data ?? [], $result['data']);
                        $order->save();

                        return response()->json(['success' => true, 'message' => 'Status updated', 'data' => $result['data']]);
                    }
                }

                return response()->json(['success' => false, 'message' => 'Unable to query status']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }
        })->name('query-status');

        // Store new order
        Route::post('/store', [OrderController::class, 'storeFromWeb'])->name('store');

        // Edit order
        Route::get('/{order}/edit', [DashboardController::class, 'editOrder'])->name('edit');
        Route::put('/{order}', [DashboardController::class, 'updateOrder'])->name('update');

        // Bulk operations
        Route::post('/bulk-action', [DashboardController::class, 'bulkAction'])->name('bulk');

        // Export orders
        Route::get('/export/{format}', [DashboardController::class, 'exportOrders'])->name('export');

        // Order invoice/receipt
        Route::get('/{order}/invoice', [DashboardController::class, 'orderInvoice'])->name('invoice');
    });

    // Queue Monitor Routes
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/monitor', [DashboardController::class, 'queueMonitor'])->name('monitor');
    });

    // API Logs Routes
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [DashboardController::class, 'apiLogs'])->name('index');
        Route::get('/{log}', [DashboardController::class, 'showLog'])->name('show');
        Route::delete('/{log}', [DashboardController::class, 'deleteLog'])
            ->middleware('joytel.role:dev')
            ->name('destroy');
        Route::post('/clear-old', [DashboardController::class, 'clearOldLogs'])
            ->middleware('joytel.role:dev')
            ->name('clear-old');
    });

    // Product Management Routes
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}', [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/sync', [ProductController::class, 'syncFromApi'])->name('sync');
        Route::post('/bulk-action', [ProductController::class, 'bulkAction'])->name('bulk');
    });

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/orders', [ReportController::class, 'orders'])->name('orders');
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/api-usage', [ReportController::class, 'apiUsage'])->name('api-usage');
        Route::get('/system', [ReportController::class, 'system'])->name('system');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });

    // Settings & Admin Routes (Dev only)
    Route::prefix('settings')->name('settings.')->middleware('joytel.role:dev')->group(function () {
        Route::get('/', [AdminController::class, 'settings'])->name('index');
        Route::post('/', [AdminController::class, 'updateSettings'])->name('update');
        Route::get('/joytel', [AdminController::class, 'joytelSettings'])->name('joytel');
        Route::post('/joytel', [AdminController::class, 'updateJoytelSettings'])->name('joytel.update');
        Route::get('/system', [AdminController::class, 'systemInfo'])->name('system');
        Route::post('/test-connection', [AdminController::class, 'testConnection'])->name('test-connection');
    });

    // Documentation & Help Routes
    Route::prefix('help')->name('help.')->group(function () {
        Route::get('/', [DashboardController::class, 'help'])->name('index');
        Route::get('/api-docs', [DashboardController::class, 'apiDocs'])->name('api-docs');
        Route::get('/integration', [DashboardController::class, 'integration'])->name('integration');
    });

    // System Maintenance Routes (Dev only)
    Route::prefix('system')->name('system.')->middleware('joytel.role:dev')->group(function () {
        Route::get('/status', [AdminController::class, 'systemStatus'])->name('status');
        Route::post('/cache/clear', [AdminController::class, 'clearCache'])->name('cache.clear');
        Route::post('/queue/restart', [AdminController::class, 'restartQueue'])->name('queue.restart');
        Route::get('/logs/laravel', [AdminController::class, 'laravelLogs'])->name('logs.laravel');
    });
});

// Error Pages (for custom error handling)
Route::get('/error/{code}', function($code) {
    $codes = ['403', '404', '500', '503'];
    if (in_array($code, $codes)) {
        return view("errors.{$code}");
    }
    abort(404);
})->name('error');
