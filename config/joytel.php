<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JoyTel API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for JoyTel API integration including Warehouse and RSP systems
    |
    */

    // Customer credentials
    'customer_code' => env('JOYTEL_CUSTOMER_CODE'),
    'customer_auth' => env('JOYTEL_CUSTOMER_AUTH'),
    
    // RSP API credentials
    'app_id' => env('JOYTEL_APP_ID'),
    'app_secret' => env('JOYTEL_APP_SECRET'),
    
    // Base URLs
    'warehouse_base_url' => env('JOYTEL_WAREHOUSE_BASE_URL', 'https://api.joytelshop.com'),
    'rsp_base_url' => env('JOYTEL_RSP_BASE_URL', 'https://esim.joytelecom.com/openapi'),
    
    // Warehouse API Endpoints
    'warehouse_endpoints' => [
        'order_submit' => '/customerApi/customerOrder',
        'order_query' => '/customerApi/customerOrder/query',
        'ota_recharge' => '/joyRechargeApi/rechargeOrder',
        'ota_recharge_query' => '/joyRechargeApi/rechargeOrder/query',
        // Product endpoints (hypothetical - should exist in real API)
        'products_list' => '/customerApi/products',
        'products_categories' => '/customerApi/products/categories',
    ],
    
    // RSP API Endpoints
    'rsp_endpoints' => [
        'service_order_query' => '/openapi/sim/subscription/query',
        'coupon_query' => '/openapi/coupon/query',
        'coupon_redeem' => '/openapi/coupon/redeem',
        'transaction_status' => '/openapi/getTransactionStatus',
        'esim_usage_query' => '/openapi/esim/usage/query',
        'esim_status_query' => '/openapi/esim/status/query',
        'esim_profile_query' => '/openapi/esim/profile/query',
        'sim_status_query' => '/openapi/sim/status/query',
        'sim_usage_query' => '/openapi/sim/usage/query',
    ],
    
    // Webhook endpoints
    'webhook_endpoints' => [
        'coupon_redeem_notify' => '/notify/coupon/redeem',
        'esim_progress_notify' => '/notify/esim/esim-progress',
    ],
    
    // Timeout settings
    'timeout' => env('JOYTEL_API_TIMEOUT', 30),
    
    // Signature validation settings
    'signature_tolerance' => env('JOYTEL_SIGNATURE_TOLERANCE', 600), // 10 minutes
    
    // Error codes
    'error_codes' => [
        '000' => 'Success',
        '401' => 'Invalid customer ID',
        '403' => 'Encryption failed',
        '407' => 'IP not whitelist',
        '500' => 'Parameter exception',
        '600' => 'Business processing failed',
        '999' => 'System exception',
    ],
];