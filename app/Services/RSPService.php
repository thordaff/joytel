<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RSPService extends JoytelService
{
    protected $appId;
    protected $appSecret;

    public function __construct()
    {
        parent::__construct();
        
        $this->baseUrl = config('joytel.rsp_base_url');
        $this->systemType = 'rsp';
        $this->appId = config('joytel.app_id');
        $this->appSecret = config('joytel.app_secret');
        
        if (!$this->appId || !$this->appSecret) {
            throw new \Exception('JoyTel RSP credentials not configured');
        }
    }

    /**
     * Query service order list
     */
    public function queryServiceOrderList(array $params = []): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = array_merge([
            'pageIndex' => $params['page_index'] ?? 1,
            'pageSize' => $params['page_size'] ?? 10,
        ], $params);

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.service_order_query'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'query service order list');
        }
    }

    /**
     * Query coupon information
     */
    public function queryCouponInfo(string $couponCode): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'couponCode' => $couponCode
        ];

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.coupon_query'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'query coupon info', $couponCode);
        }
    }

    /**
     * Redeem coupon
     */
    public function redeemCoupon(string $couponCode, ?string $orderTid = null): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'couponCode' => $couponCode
        ];

        if ($orderTid) {
            $headers['OrderTid'] = $orderTid;
        }

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.coupon_redeem'),
                $requestData,
                $headers
            );

            $result = $this->processRSPResponse($response);
            
            if ($result['success']) {
                Log::info("Coupon redeemed successfully", [
                    'coupon_code' => $couponCode,
                    'order_tid' => $orderTid,
                    'response' => $result['data']
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'redeem coupon', $couponCode);
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus(string $transactionId): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'transactionId' => $transactionId
        ];

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.transaction_status'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'get transaction status', $transactionId);
        }
    }

    /**
     * Query eSIM usage
     */
    public function queryESIMUsage(string $iccid): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'iccid' => $iccid
        ];

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.esim_usage_query'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'query eSIM usage', $iccid);
        }
    }

    /**
     * Query eSIM status
     */
    public function queryESIMStatus(string $iccid): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'iccid' => $iccid
        ];

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.esim_status_query'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'query eSIM status', $iccid);
        }
    }

    /**
     * Query eSIM profile information
     */
    public function queryESIMProfile(string $iccid): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'iccid' => $iccid
        ];

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.esim_profile_query'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'query eSIM profile', $iccid);
        }
    }

    /**
     * Query OTA card status
     */
    public function queryOTACardStatus(string $snPin): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'snPin' => $snPin
        ];

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.sim_status_query'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'query OTA card status', $snPin);
        }
    }

    /**
     * Query OTA card usage
     */
    public function queryOTACardUsage(string $snPin): array
    {
        $headers = $this->generateRSPHeaders();
        
        $requestData = [
            'snPin' => $snPin
        ];

        try {
            $response = $this->makeRequest(
                config('joytel.rsp_endpoints.sim_usage_query'),
                $requestData,
                $headers
            );

            return $this->processRSPResponse($response);

        } catch (\Exception $e) {
            return $this->handleRSPException($e, 'query OTA card usage', $snPin);
        }
    }

    /**
     * Generate RSP API headers
     */
    private function generateRSPHeaders(): array
    {
        $transId = $this->generateTransactionId();
        $timestamp = $this->getCurrentTimestamp();
        
        // Generate ciphertext using MD5
        $ciphertext = md5($this->appId . $transId . $timestamp . $this->appSecret);

        return [
            'AppId' => $this->appId,
            'TransId' => $transId,
            'Timestamp' => (string) $timestamp,
            'Ciphertext' => $ciphertext,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    /**
     * Validate RSP webhook signature
     */
    public function validateWebhookSignature(array $headers, array $data): bool
    {
        $appId = $headers['AppId'] ?? $headers['appid'] ?? null;
        $transId = $headers['TransId'] ?? $headers['transid'] ?? null;
        $timestamp = $headers['Timestamp'] ?? $headers['timestamp'] ?? null;
        $receivedCiphertext = $headers['Ciphertext'] ?? $headers['ciphertext'] ?? null;

        if (!$appId || !$transId || !$timestamp || !$receivedCiphertext) {
            Log::warning("Missing required headers for webhook validation", [
                'headers' => $headers
            ]);
            return false;
        }

        // Validate timestamp
        if (!$this->validateTimestamp((int) $timestamp)) {
            Log::warning("Invalid timestamp in webhook", [
                'timestamp' => $timestamp,
                'current_time' => time()
            ]);
            return false;
        }

        // Validate app ID
        if ($appId !== $this->appId) {
            Log::warning("Invalid app ID in webhook", [
                'received_app_id' => $appId,
                'expected_app_id' => $this->appId
            ]);
            return false;
        }

        // Generate expected ciphertext
        $expectedCiphertext = md5($appId . $transId . $timestamp . $this->appSecret);

        if ($receivedCiphertext !== $expectedCiphertext) {
            Log::warning("Invalid signature in webhook", [
                'received_signature' => $receivedCiphertext,
                'expected_signature' => $expectedCiphertext
            ]);
            return false;
        }

        return true;
    }

    /**
     * Process RSP API response
     */
    private function processRSPResponse(array $response): array
    {
        if ($response['success']) {
            return [
                'success' => true,
                'data' => $response['data']
            ];
        } else {
            return [
                'success' => false,
                'error' => $this->handleErrorResponse($response['data'])
            ];
        }
    }

    /**
     * Handle RSP exceptions
     */
    private function handleRSPException(\Exception $e, string $operation, ?string $identifier = null): array
    {
        Log::error("Failed to $operation", [
            'identifier' => $identifier,
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'error' => [
                'error_code' => '999',
                'error_message' => 'System exception: ' . $e->getMessage()
            ]
        ];
    }
}