<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\RSPService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class RedeemCouponJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 3; // Maximum 3 attempts
    public $backoff = [30, 60, 120]; // Backoff delays in seconds

    private string $orderTid;
    private string $couponCode;
    private ?array $additionalData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $orderTid, string $couponCode, ?array $additionalData = null)
    {
        $this->orderTid = $orderTid;
        $this->couponCode = $couponCode;
        $this->additionalData = $additionalData;
        
        // Set queue and delay if needed
        $this->onQueue('joytel');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting coupon redemption job", [
            'order_tid' => $this->orderTid,
            'coupon_code' => $this->couponCode,
            'attempt' => $this->attempts()
        ]);

        try {
            DB::beginTransaction();

            // Find the order
            $order = Order::where('order_tid', $this->orderTid)->first();
            
            if (!$order) {
                throw new Exception("Order not found with TID: {$this->orderTid}");
            }

            // Check if order is already completed or failed
            if ($order->isCompleted()) {
                Log::info("Order already completed, skipping coupon redemption", [
                    'order_tid' => $this->orderTid
                ]);
                DB::rollBack();
                return;
            }

            if ($order->hasFailed()) {
                Log::warning("Order has failed, cannot redeem coupon", [
                    'order_tid' => $this->orderTid,
                    'status' => $order->status
                ]);
                DB::rollBack();
                return;
            }

            // Mark order as processing
            $order->markAsProcessing();

            // Initialize RSP service
            $rspService = new RSPService();

            // First, query coupon info to validate
            Log::info("Querying coupon information", [
                'coupon_code' => $this->couponCode
            ]);

            $couponInfo = $rspService->queryCouponInfo($this->couponCode);
            
            if (!$couponInfo['success']) {
                throw new Exception("Failed to query coupon info: " . json_encode($couponInfo['error']));
            }

            Log::info("Coupon info retrieved successfully", [
                'coupon_code' => $this->couponCode,
                'coupon_info' => $couponInfo['data']
            ]);

            // Attempt to redeem the coupon
            Log::info("Attempting to redeem coupon", [
                'coupon_code' => $this->couponCode,
                'order_tid' => $this->orderTid
            ]);

            $redeemResult = $rspService->redeemCoupon($this->couponCode, $this->orderTid);

            if (!$redeemResult['success']) {
                $errorMessage = "Failed to redeem coupon: " . json_encode($redeemResult['error']);
                
                Log::error($errorMessage, [
                    'order_tid' => $this->orderTid,
                    'coupon_code' => $this->couponCode,
                    'error' => $redeemResult['error']
                ]);

                // Update order with error info
                $order->markAsFailed($errorMessage, [
                    'coupon_redemption_error' => $redeemResult['error'],
                    'coupon_info' => $couponInfo['data'] ?? null
                ]);

                DB::commit();
                throw new Exception($errorMessage);
            }

            // Successfully redeemed
            Log::info("Coupon redeemed successfully", [
                'order_tid' => $this->orderTid,
                'coupon_code' => $this->couponCode,
                'redeem_result' => $redeemResult['data']
            ]);

            // Update order with redemption data
            $responseData = $order->response_data ?? [];
            $responseData['coupon_redeemed'] = true;
            $responseData['coupon_redemption_data'] = $redeemResult['data'];
            $responseData['coupon_info'] = $couponInfo['data'];
            
            if ($this->additionalData) {
                $responseData = array_merge($responseData, $this->additionalData);
            }

            $order->response_data = $responseData;
            
            // Check if we have QR code data in the redeem result
            if (isset($redeemResult['data']['qrcode']) || isset($redeemResult['data']['qrCode'])) {
                $qrCode = $redeemResult['data']['qrcode'] ?? $redeemResult['data']['qrCode'];
                $order->setQrCode($qrCode);
                $order->markAsCompleted($responseData);
                
                Log::info("Order completed with QR code", [
                    'order_tid' => $this->orderTid,
                    'has_qr_code' => !empty($qrCode)
                ]);
            } else {
                // Keep as processing, waiting for QR code callback
                $order->save();
                
                Log::info("Coupon redeemed, waiting for QR code callback", [
                    'order_tid' => $this->orderTid
                ]);
            }

            DB::commit();

            Log::info("Coupon redemption job completed successfully", [
                'order_tid' => $this->orderTid,
                'coupon_code' => $this->couponCode
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("Coupon redemption job failed", [
                'order_tid' => $this->orderTid,
                'coupon_code' => $this->couponCode,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);

            // If this is the last attempt, mark order as failed
            if ($this->attempts() >= $this->tries) {
                try {
                    DB::beginTransaction();
                    $order = Order::where('order_tid', $this->orderTid)->first();
                    if ($order && !$order->hasFailed()) {
                        $order->markAsFailed("Coupon redemption failed after {$this->tries} attempts: " . $e->getMessage());
                    }
                    DB::commit();
                } catch (Exception $failureUpdateError) {
                    DB::rollBack();
                    Log::error("Failed to update order failure status", [
                        'order_tid' => $this->orderTid,
                        'error' => $failureUpdateError->getMessage()
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Coupon redemption job failed permanently", [
            'order_tid' => $this->orderTid,
            'coupon_code' => $this->couponCode,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Try to update order status one more time
        try {
            DB::beginTransaction();
            $order = Order::where('order_tid', $this->orderTid)->first();
            if ($order && !$order->hasFailed()) {
                $order->markAsFailed("Coupon redemption job failed permanently: " . $exception->getMessage());
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to update order after job failure", [
                'order_tid' => $this->orderTid,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15); // Total timeout of 15 minutes
    }
}
