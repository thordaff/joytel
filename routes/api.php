<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Order Management Routes
Route::prefix('orders')->group(function () {
    // Submit new eSIM order
    Route::post('/', [OrderController::class, 'submitOrder']);
    
    // Query order status
    Route::get('/query', [OrderController::class, 'queryOrder']);
    
    // List orders with pagination
    Route::get('/', [OrderController::class, 'listOrders']);
    
    // Submit OTA recharge order
    Route::post('/ota-recharge', [OrderController::class, 'submitOTARecharge']);
});

// Webhook Routes (no authentication required for external webhooks)
Route::prefix('webhook')->group(function () {
    // Warehouse system webhooks
    Route::post('/sn-pin', [WebhookController::class, 'handleSnPinCallback']);
    Route::post('/qr-code', [WebhookController::class, 'handleQrCodeCallback']);
    
    // RSP system webhooks
    Route::post('/notify/coupon/redeem', [WebhookController::class, 'handleCouponRedeemNotification']);
    Route::post('/notify/esim/esim-progress', [WebhookController::class, 'handleESIMProgressNotification']);
});

// Additional utility routes
Route::prefix('utils')->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'OK',
            'timestamp' => now()->toISOString(),
            'service' => 'JoyTel Integration API'
        ]);
    });
    
    // Configuration check (for debugging)
    Route::get('/config', function () {
        return response()->json([
            'warehouse_configured' => !empty(config('joytel.customer_code')) && !empty(config('joytel.customer_auth')),
            'rsp_configured' => !empty(config('joytel.app_id')) && !empty(config('joytel.app_secret')),
            'warehouse_url' => config('joytel.warehouse_base_url'),
            'rsp_url' => config('joytel.rsp_base_url'),
            'queue_connection' => config('queue.default'),
        ]);
    });
});

// API endpoint for log details (used by dashboard)
Route::get('/logs/{log}', function (App\Models\JoytelLog $log) {
    return response()->json([
        'success' => true,
        'data' => $log
    ]);
})->where('log', '[0-9]+');