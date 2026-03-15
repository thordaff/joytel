<?php
/**
 * JoyTel API Integration Examples
 * 
 * This file demonstrates how to use the JoyTel integration API
 */

$projectRoot = dirname(__DIR__);

if (file_exists($projectRoot . '/vendor/autoload.php')) {
    require_once $projectRoot . '/vendor/autoload.php';
}

echo "=== JoyTel API Integration Examples ===\n\n";

function resolveAppUrl(string $projectRoot): string {
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
    }

    $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL');

    if (!$appUrl) {
        throw new \RuntimeException('APP_URL is not set. Please configure APP_URL in your .env file.');
    }

    return rtrim($appUrl, '/');
}

// Base URL for your Laravel API (derived from APP_URL in .env)
$baseUrl = resolveAppUrl($projectRoot) . '/api';

/**
 * Example 1: Submit eSIM Order (Warehouse System)
 */
function submitESIMOrderExample() {
    global $baseUrl;
    
    $data = [
        'product_code' => 'ESIM_GLOBAL_1GB',
        'quantity' => 1,
        'receive_name' => 'John Doe',
        'phone' => '+1234567890',
        'email' => 'john@example.com',
        'cid' => 'CUSTOMER_123',
        'system_type' => 'warehouse'
    ];
    
    $response = httpRequest('POST', $baseUrl . '/orders', $data);
    
    echo "=== Submit eSIM Order Example ===\n";
    echo "REQUEST:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    echo "RESPONSE:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    
    return $response;
}

/**
 * Example 2: Query Order Status
 */
function queryOrderExample($orderTid) {
    global $baseUrl;
    
    $response = httpRequest('GET', $baseUrl . '/orders/query?order_tid=' . $orderTid);
    
    echo "=== Query Order Status Example ===\n";
    echo "REQUEST: GET /orders/query?order_tid={$orderTid}\n";
    echo "RESPONSE:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    
    return $response;
}

/**
 * Example 3: Submit OTA Recharge Order
 */
function submitOTARechargeExample() {
    global $baseUrl;
    
    $data = [
        'product_code' => 'RECHARGE_5GB',
        'sn_pin' => '1234567890123456',
        'quantity' => 1
    ];
    
    $response = httpRequest('POST', $baseUrl . '/orders/ota-recharge', $data);
    
    echo "=== Submit OTA Recharge Example ===\n";
    echo "REQUEST:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    echo "RESPONSE:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    
    return $response;
}

/**
 * Example 4: List Orders
 */
function listOrdersExample() {
    global $baseUrl;
    
    $response = httpRequest('GET', $baseUrl . '/orders?per_page=10&status=completed');
    
    echo "=== List Orders Example ===\n";
    echo "REQUEST: GET /orders?per_page=10&status=completed\n";
    echo "RESPONSE:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    
    return $response;
}

/**
 * Example 5: Warehouse Signature Generation
 */
function generateWarehouseSignatureExample() {
    $customerCode = 'YOUR_CUSTOMER_CODE';
    $customerAuth = 'YOUR_CUSTOMER_AUTH';
    $warehouse = 'warehouse';
    $type = 3;
    $orderTid = 'JT20240223123456ABCDEF';
    $receiveName = 'John Doe';
    $phone = '+1234567890';
    $timestamp = time();
    $itemList = [['productCode' => 'ESIM_GLOBAL_1GB', 'quantity' => 1]];
    
    // Convert itemList to string
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
    
    $signature = sha1($signatureString);
    
    echo "=== Warehouse Signature Generation Example ===\n";
    echo "Signature String: {$signatureString}\n";
    echo "SHA1 Signature: {$signature}\n\n";
    
    return $signature;
}

/**
 * Example 6: RSP Ciphertext Generation  
 */
function generateRSPCiphertextExample() {
    $appId = 'YOUR_APP_ID';
    $transId = 'RSP_20240223123456_ABCDEF12';
    $timestamp = time();
    $appSecret = 'YOUR_APP_SECRET';
    
    $ciphertextString = $appId . $transId . $timestamp . $appSecret;
    $ciphertext = md5($ciphertextString);
    
    echo "=== RSP Ciphertext Generation Example ===\n";
    echo "Ciphertext String: {$ciphertextString}\n";
    echo "MD5 Ciphertext: {$ciphertext}\n";
    echo "Headers:\n";
    echo json_encode([
        'AppId' => $appId,
        'TransId' => $transId,
        'Timestamp' => (string) $timestamp,
        'Ciphertext' => $ciphertext
    ], JSON_PRETTY_PRINT) . "\n\n";
    
    return $ciphertext;
}

/**
 * Example 7: Webhook Handler Simulation
 */
function simulateWebhookExample() {
    global $baseUrl;
    
    // Simulate SN/PIN webhook
    $snPinWebhook = [
        'order_tid' => 'JT20240223123456ABCDEF',
        'sn_pin' => '1234567890123456',
        'order_code' => 'JT_ORDER_123',
        'status' => 'success'
    ];
    
    echo "=== SN/PIN Webhook Simulation ===\n";
    echo "Webhook URL: POST {$baseUrl}/webhook/sn-pin\n";
    echo "Payload:\n";
    echo json_encode($snPinWebhook, JSON_PRETTY_PRINT) . "\n";
    
    // Simulate QR Code webhook
    $qrCodeWebhook = [
        'order_tid' => 'JT20240223123456ABCDEF',
        'qrcode' => 'LPA:1$esim.example.com$12345-67890-ABCDEF',
        'status' => 'completed'
    ];
    
    echo "\n=== QR Code Webhook Simulation ===\n";
    echo "Webhook URL: POST {$baseUrl}/webhook/qr-code\n";
    echo "Payload:\n";
    echo json_encode($qrCodeWebhook, JSON_PRETTY_PRINT) . "\n\n";
}

/**
 * Simple HTTP request function for examples
 */
function httpRequest($method, $url, $data = null) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return ['error' => $error];
    }
    
    return [
        'http_code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Run examples (uncomment to test)
/*
echo "Running JoyTel API Examples...\n\n";

// 1. Submit eSIM Order
$orderResponse = submitESIMOrderExample();
$orderTid = $orderResponse['data']['data']['order_tid'] ?? 'JT20240223123456ABCDEF';

// 2. Query Order
queryOrderExample($orderTid);

// 3. Submit OTA Recharge
submitOTARechargeExample();

// 4. List Orders
listOrdersExample();

// 5. Generate signatures
generateWarehouseSignatureExample();
generateRSPCiphertextExample();

// 6. Webhook simulation
simulateWebhookExample();

echo "Examples completed!\n";
*/

echo "To run these examples, uncomment the code at the bottom of this file.\n";
echo "Make sure to configure your JoyTel credentials in the .env file first.\n";
?>