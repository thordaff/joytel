<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class JoytelCredentialService
{
    /**
     * Validate JoyTel credentials
     */
    public function validateCredentials(): array
    {
        $warehouseValid = $this->validateWarehouseCredentials();
        $rspValid = $this->validateRSPCredentials();
        
        return [
            'warehouse' => $warehouseValid,
            'rsp' => $rspValid,
            'overall_status' => $warehouseValid['valid'] || $rspValid['valid']
        ];
    }

    /**
     * Validate Warehouse API credentials
     */
    private function validateWarehouseCredentials(): array
    {
        $customerCode = config('joytel.customer_code');
        $customerAuth = config('joytel.customer_auth');
        
        // Check if credentials are set
        if (empty($customerCode) || empty($customerAuth)) {
            return [
                'valid' => false,
                'status' => 'missing',
                'message' => 'Warehouse credentials not configured',
                'action_required' => 'Set JOYTEL_CUSTOMER_CODE and JOYTEL_CUSTOMER_AUTH in .env file'
            ];
        }
        
        // Check if using demo/test credentials
        if (str_contains(strtolower($customerCode), 'test') || str_contains(strtolower($customerCode), 'demo')) {
            return [
                'valid' => false,
                'status' => 'demo',
                'message' => 'Using demo/test credentials',
                'action_required' => 'Replace with production credentials from JoyTel'
            ];
        }
        
        // Validate credential format (JoyTel specific patterns)
        if (!$this->isValidWarehouseCredentialFormat($customerCode, $customerAuth)) {
            return [
                'valid' => false,
                'status' => 'invalid_format',
                'message' => 'Invalid credential format',
                'action_required' => 'Verify credentials received from JoyTel partnership'
            ];
        }
        
        // Test connection with JoyTel API
        return $this->testWarehouseConnection($customerCode, $customerAuth);
    }

    /**
     * Validate RSP API credentials  
     */
    private function validateRSPCredentials(): array
    {
        $appId = config('joytel.app_id');
        $appSecret = config('joytel.app_secret');
        
        if (empty($appId) || empty($appSecret)) {
            return [
                'valid' => false,
                'status' => 'missing',
                'message' => 'RSP credentials not configured',
                'action_required' => 'Set JOYTEL_APP_ID and JOYTEL_APP_SECRET in .env file'
            ];
        }
        
        // Test RSP connection
        return $this->testRSPConnection($appId, $appSecret);
    }

    /**
     * Check if warehouse credentials follow JoyTel format
     */
    private function isValidWarehouseCredentialFormat(string $customerCode, string $customerAuth): bool
    {
        // JoyTel customer codes typically follow patterns like:
        // - CUST12345
        // - JT_PARTNER_001
        // - Alphanumeric, 6-20 characters
        
        if (!preg_match('/^[A-Z0-9_]{6,20}$/i', $customerCode)) {
            return false;
        }
        
        // Customer auth is typically a hash or key, 20+ characters
        if (strlen($customerAuth) < 20) {
            return false;
        }
        
        return true;
    }

    /**
     * Test actual connection to Warehouse API
     */
    private function testWarehouseConnection(string $customerCode, string $customerAuth): array
    {
        try {
            $cacheKey = "joytel_warehouse_test_{$customerCode}";
            
            // Cache validation result for 5 minutes
            return Cache::remember($cacheKey, 300, function() use ($customerCode, $customerAuth) {
                
                // Create a minimal test request to validate credentials
                $timestamp = time();
                $testOrderTid = 'TEST_' . $timestamp;
                
                $signature = sha1($customerCode . $customerAuth . 'warehouse' . '3' . $testOrderTid . 'TestName' . '1234567890' . $timestamp . 'TEST_PRODUCT:1');
                
                $testData = [
                    'customerCode' => $customerCode,
                    'warehouse' => 'warehouse',
                    'type' => '3', // Query type
                    'orderTid' => $testOrderTid,
                    'receiveName' => 'TestName',
                    'phone' => '1234567890',
                    'timestamp' => $timestamp,
                    'itemList' => 'TEST_PRODUCT:1',
                    'signature' => $signature
                ];
                
                $response = Http::timeout(10)->post(
                    config('joytel.warehouse_base_url') . config('joytel.warehouse_endpoints.order_query'),
                    $testData
                );
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Check if credentials are accepted (not necessarily successful order)
                    if (isset($data['code']) && $data['code'] !== '401' && $data['code'] !== '403') {
                        return [
                            'valid' => true,
                            'status' => 'connected',
                            'message' => 'Warehouse API connection successful',
                            'last_tested' => now()->format('Y-m-d H:i:s')
                        ];
                    }
                }
                
                return [
                    'valid' => false,
                    'status' => 'connection_failed',
                    'message' => 'Unable to authenticate with Warehouse API',
                    'action_required' => 'Verify credentials with JoyTel support'
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('JoyTel Warehouse connection test failed', [
                'customer_code' => $customerCode,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'status' => 'connection_error',
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'action_required' => 'Check network connectivity and API endpoints'
            ];
        }
    }

    /**
     * Test RSP API connection
     */
    private function testRSPConnection(string $appId, string $appSecret): array
    {
        try {
            $cacheKey = "joytel_rsp_test_{$appId}";
            
            return Cache::remember($cacheKey, 300, function() use ($appId, $appSecret) {
                
                $timestamp = time();
                $transId = 'TEST_' . $timestamp;
                $ciphertext = md5($appId . $transId . $timestamp . $appSecret);
                
                $testData = [
                    'appId' => $appId,
                    'transId' => $transId,
                    'timestamp' => $timestamp,
                    'ciphertext' => $ciphertext
                ];
                
                // Use a lightweight endpoint for testing
                $response = Http::timeout(10)->post(
                    config('joytel.rsp_base_url') . '/openapi/getTransactionStatus',
                    $testData
                );
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['code']) && $data['code'] !== '401' && $data['code'] !== '403') {
                        return [
                            'valid' => true,
                            'status' => 'connected',
                            'message' => 'RSP API connection successful',
                            'last_tested' => now()->format('Y-m-d H:i:s')
                        ];
                    }
                }
                
                return [
                    'valid' => false,
                    'status' => 'connection_failed',
                    'message' => 'Unable to authenticate with RSP API',
                    'action_required' => 'Verify APP_ID and APP_SECRET with JoyTel'
                ];
            });
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'status' => 'connection_error',
                'message' => 'RSP connection test failed: ' . $e->getMessage(),
                'action_required' => 'Check RSP API configuration'
            ];
        }
    }

    /**
     * Get JoyTel partnership information
     */
    public function getPartnershipInfo(): array
    {
        return [
            'partnership_email' => 'business@joytel.com',
            'technical_support' => 'support@joytel.com',
            'documentation' => 'https://developer.joytel.com',
            'business_website' => 'https://www.joytel.com/partnership',
            'required_documents' => [
                'Company registration certificate',
                'Business license',
                'Tax identification number',
                'Bank account information',
                'Technical contact details',
                'API integration proposal'
            ],
            'integration_process' => [
                '1. Submit partnership application',
                '2. Business verification (1-2 weeks)',
                '3. Technical documentation access',
                '4. Sandbox credentials provided',
                '5. Integration testing & certification',
                '6. Production credentials issued'
            ],
            'sandbox_environment' => [
                'available' => config('joytel.sandbox_mode', false),
                'warehouse_url' => 'https://sandbox-api.joytelshop.com',
                'rsp_url' => 'https://sandbox-esim.joytelecom.com/openapi'
            ]
        ];
    }
}