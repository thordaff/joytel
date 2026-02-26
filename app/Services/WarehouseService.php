<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class WarehouseService extends JoytelService
{
    protected $customerCode;
    protected $customerAuth;

    public function __construct()
    {
        parent::__construct();
        
        $this->baseUrl = config('joytel.warehouse_base_url');
        $this->systemType = 'warehouse';
        $this->customerCode = config('joytel.customer_code');
        $this->customerAuth = config('joytel.customer_auth');
        
        if (!$this->customerCode || !$this->customerAuth) {
            throw new \Exception('JoyTel Warehouse credentials not configured');
        }
    }

    /**
     * Submit eSIM order
     */
    public function submitESIMOrder(array $orderData): array
    {
        $timestamp = $this->getCurrentTimestamp();
        $orderTid = $orderData['order_tid'] ?? Order::generateOrderTid();
        
        // Prepare request data
        $requestData = [
            'customerCode' => $this->customerCode,
            'orderTid' => $orderTid,
            'type' => 3, // eSIM type
            'receiveName' => $orderData['receive_name'],
            'phone' => $orderData['phone'],
            'timestamp' => $timestamp,
            'email' => $orderData['email'] ?? '',
            'itemList' => [[
                'productCode' => $orderData['product_code'],
                'quantity' => $orderData['quantity'] ?? 1
            ]]
        ];

        // Generate signature
        $signature = $this->generateWarehouseSignature(
            $this->customerCode,
            $this->customerAuth,
            'warehouse',
            3,
            $orderTid,
            $orderData['receive_name'],
            $orderData['phone'],
            $timestamp,
            $requestData['itemList']
        );

        $requestData['autoGraph'] = $signature;

        try {
            $response = $this->makeRequest(
                config('joytel.warehouse_endpoints.order_submit'),
                $requestData
            );

            if ($response['success']) {
                Log::info("eSIM Order submitted successfully", [
                    'order_tid' => $orderTid,
                    'response' => $response['data']
                ]);
                
                return [
                    'success' => true,
                    'order_tid' => $orderTid,
                    'order_code' => $response['data']['orderCode'] ?? null,
                    'data' => $response['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $this->handleErrorResponse($response['data'])
                ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to submit eSIM order", [
                'order_tid' => $orderTid,
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

    /**
     * Query eSIM order status
     */
    public function queryESIMOrder(string $orderCode, string $orderTid): array
    {
        $timestamp = $this->getCurrentTimestamp();

        // Prepare request data
        $requestData = [
            'customerCode' => $this->customerCode,
            'orderCode' => $orderCode,
            'orderTid' => $orderTid,
            'timestamp' => $timestamp
        ];

        // Generate signature
        $signature = $this->generateOrderQuerySignature(
            $this->customerCode,
            $this->customerAuth,
            $orderCode,
            $orderTid,
            $timestamp
        );

        $requestData['autoGraph'] = $signature;

        try {
            $response = $this->makeRequest(
                config('joytel.warehouse_endpoints.order_query'),
                $requestData
            );

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

        } catch (\Exception $e) {
            Log::error("Failed to query eSIM order", [
                'order_code' => $orderCode,
                'order_tid' => $orderTid,
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

    /**
     * Submit OTA recharge order
     */
    public function submitOTARecharge(array $orderData): array
    {
        $timestamp = $this->getCurrentTimestamp();
        $orderTid = $orderData['order_tid'] ?? Order::generateOrderTid();

        // Prepare request data
        $requestData = [
            'customerCode' => $this->customerCode,
            'timestamp' => $timestamp,
            'orderTid' => $orderTid,
            'itemList' => [[
                'productCode' => $orderData['product_code'],
                'quantity' => $orderData['quantity'] ?? 1,
                'snPin' => $orderData['sn_pin']
            ]]
        ];

        // Generate signature
        $signature = $this->generateOTASignature(
            $this->customerCode,
            $this->customerAuth,
            $timestamp,
            $requestData['itemList'],
            $orderTid
        );

        $requestData['autoGraph'] = $signature;

        try {
            $response = $this->makeRequest(
                config('joytel.warehouse_endpoints.ota_recharge'),
                $requestData
            );

            if ($response['success']) {
                return [
                    'success' => true,
                    'order_tid' => $orderTid,
                    'data' => $response['data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $this->handleErrorResponse($response['data'])
                ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to submit OTA recharge", [
                'order_tid' => $orderTid,
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

    /**
     * Query OTA recharge status
     */
    public function queryOTAStatus(string $orderTid): array
    {
        $timestamp = $this->getCurrentTimestamp();

        // Prepare request data
        $requestData = [
            'customerCode' => $this->customerCode,
            'orderTid' => $orderTid,
            'timestamp' => $timestamp
        ];

        // Generate signature (same as OTA recharge signature but without itemList)
        $signature = sha1(
            $this->customerCode . 
            $this->customerAuth . 
            $timestamp . 
            $orderTid
        );

        $requestData['autoGraph'] = $signature;

        try {
            $response = $this->makeRequest(
                config('joytel.warehouse_endpoints.ota_recharge_query'),
                $requestData
            );

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

        } catch (\Exception $e) {
            Log::error("Failed to query OTA status", [
                'order_tid' => $orderTid,
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

    /**
     * Generate warehouse signature (SHA1)
     */
    private function generateWarehouseSignature(
        string $customerCode,
        string $customerAuth,
        string $warehouse,
        int $type,
        string $orderTid,
        string $receiveName,
        string $phone,
        int $timestamp,
        array $itemList
    ): string {
        // Convert itemList to string format
        $itemListStr = '';
        foreach ($itemList as $item) {
            $itemListStr .= $item['productCode'] . $item['quantity'];
        }

        $signatureString = $customerCode . 
                          $customerAuth . 
                          $warehouse . 
                          $type . 
                          $orderTid . 
                          $receiveName . 
                          $phone . 
                          $timestamp . 
                          $itemListStr;

        Log::debug("Warehouse signature string", [
            'signature_string' => $signatureString,
            'sha1' => sha1($signatureString)
        ]);

        return sha1($signatureString);
    }

    /**
     * Generate order query signature (SHA1)
     */
    private function generateOrderQuerySignature(
        string $customerCode,
        string $customerAuth,
        string $orderCode,
        string $orderTid,
        int $timestamp
    ): string {
        $signatureString = $customerCode . 
                          $customerAuth . 
                          $orderCode . 
                          $orderTid . 
                          $timestamp;

        return sha1($signatureString);
    }

    /**
     * Generate OTA signature (SHA1)
     */
    private function generateOTASignature(
        string $customerCode,
        string $customerAuth,
        int $timestamp,
        array $itemList,
        string $orderTid
    ): string {
        // Convert itemList to string format
        $itemListStr = '';
        foreach ($itemList as $item) {
            $itemListStr .= $item['productCode'] . $item['quantity'] . ($item['snPin'] ?? '');
        }

        $signatureString = $customerCode . 
                          $customerAuth . 
                          $timestamp . 
                          $itemListStr . 
                          $orderTid;

        return sha1($signatureString);
    }
}