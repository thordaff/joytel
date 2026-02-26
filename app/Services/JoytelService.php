<?php

namespace App\Services;

use App\Models\JoytelLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

abstract class JoytelService
{
    protected $baseUrl;
    protected $timeout;
    protected $systemType;

    public function __construct()
    {
        $this->timeout = config('joytel.timeout', 30);
    }

    /**
     * Make HTTP request to JoyTel API
     */
    protected function makeRequest(
        string $endpoint,
        array $data = [],
        array $headers = [],
        string $method = 'POST'
    ): array {
        $startTime = microtime(true);
        $transactionId = $this->generateTransactionId();
        $url = $this->baseUrl . $endpoint;

        try {
            Log::info("JoyTel API Request", [
                'transaction_id' => $transactionId,
                'system_type' => $this->systemType,
                'url' => $url,
                'method' => $method,
                'headers' => $headers,
                'data' => $data
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->{strtolower($method)}($url, $data);

            $responseTime = round(microtime(true) - $startTime, 2);
            $responseData = $response->json();

            // Log the response
            $this->logRequest(
                $transactionId,
                $endpoint,
                $method,
                $headers,
                $data,
                $response->headers(),
                $responseData,
                $response->status(),
                $responseTime,
                $responseData['code'] ?? null
            );

            Log::info("JoyTel API Response", [
                'transaction_id' => $transactionId,
                'response_time' => $responseTime,
                'status' => $response->status(),
                'response' => $responseData
            ]);

            return [
                'success' => $this->isSuccessResponse($response, $responseData),
                'data' => $responseData,
                'status_code' => $response->status(),
                'response_time' => $responseTime,
                'transaction_id' => $transactionId
            ];

        } catch (\Exception $e) {
            $responseTime = round(microtime(true) - $startTime, 2);
            
            Log::error("JoyTel API Error", [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'url' => $url,
                'response_time' => $responseTime
            ]);

            // Log the error
            $this->logRequest(
                $transactionId,
                $endpoint,
                $method,
                $headers,
                $data,
                [],
                null,
                null,
                $responseTime,
                null,
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Check if response is successful
     */
    protected function isSuccessResponse(Response $response, ?array $responseData): bool
    {
        // Check HTTP status
        if ($response->status() < 200 || $response->status() >= 300) {
            return false;
        }

        // Check JoyTel response code
        if (isset($responseData['code']) && $responseData['code'] !== '000') {
            return false;
        }

        return true;
    }

    /**
     * Log API request and response
     */
    protected function logRequest(
        string $transactionId,
        string $endpoint,
        string $method,
        array $requestHeaders,
        array $requestBody,
        array $responseHeaders,
        ?array $responseBody,
        ?int $responseStatus,
        float $responseTime,
        ?string $responseCode,
        ?string $errorMessage = null,
        ?string $orderTid = null
    ): void {
        JoytelLog::create([
            'transaction_id' => $transactionId,
            'system_type' => $this->systemType,
            'endpoint' => $endpoint,
            'method' => $method,
            'request_headers' => $requestHeaders,
            'request_body' => $requestBody,
            'response_headers' => $responseHeaders,
            'response_body' => $responseBody,
            'response_status' => $responseStatus,
            'response_code' => $responseCode,
            'response_time' => $responseTime,
            'order_tid' => $orderTid,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Generate unique transaction ID
     */
    protected function generateTransactionId(): string
    {
        return strtoupper($this->systemType) . '_' . date('YmdHis') . '_' . strtoupper(Str::random(8));
    }

    /**
     * Validate timestamp tolerance
     */
    protected function validateTimestamp(int $timestamp): bool
    {
        $now = time();
        $tolerance = config('joytel.signature_tolerance', 600); // 10 minutes
        
        return abs($now - $timestamp) <= $tolerance;
    }

    /**
     * Get current timestamp
     */
    protected function getCurrentTimestamp(): int
    {
        return time();
    }

    /**
     * Handle API error responses
     */
    protected function handleErrorResponse(array $responseData): array
    {
        $code = $responseData['code'] ?? '999';
        $message = $responseData['msg'] ?? 'Unknown error';
        
        $errorCodes = config('joytel.error_codes');
        $errorMessage = $errorCodes[$code] ?? $message;

        Log::warning("JoyTel API Error Response", [
            'code' => $code,
            'message' => $message,
            'error_meaning' => $errorMessage
        ]);

        return [
            'error_code' => $code,
            'error_message' => $errorMessage,
            'original_message' => $message,
            'data' => $responseData
        ];
    }
}