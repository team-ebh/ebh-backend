<?php

declare(strict_types=1);

namespace App\Services\Payment\Traits;

use App\Enums\Payment\PaymentLogTypeEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Makes HTTP Requests Trait
 *
 * Reusable HTTP client logic for payment gateways
 * Single Responsibility: HTTP communication
 */
trait MakesHttpRequests
{
    /**
     * Make HTTP request to payment gateway
     *
     * @param  PaymentLogTypeEnum  $type  Type of payment operation (generate_link, check_status)
     * @param  string  $method  HTTP method (get, post, put, delete)
     * @param  string  $url  Full URL to request
     * @param  array  $data  Request payload
     * @param  array  $headers  Additional headers
     * @return array{success: bool, status_code: int, data: array, response_time: float}
     *
     * @throws \Throwable
     */
    protected function makeHttpRequest(
        PaymentLogTypeEnum $type,
        string $method,
        string $url,
        array $data = [],
        array $headers = [],
        ?int $paymentId = null
    ): array {
        $startTime = microtime(true);

        return safeProcess()
            ->onFailed(function ($e) use ($type, $method, $url, $data, $startTime, $paymentId) {
                $responseTime = $this->calculateResponseTime($startTime);

                $this->logHttpRequest(
                    type: $type,
                    method: $method,
                    url: $url,
                    requestData: $data,
                    responseData: ['error' => $e->getMessage()],
                    responseHeaders: [],
                    statusCode: 0,
                    responseTime: $responseTime,
                    paymentId: $paymentId,
                    error: $e->getMessage()
                );

                Log::error('Payment gateway HTTP request failed', [
                    'gateway' => $this->getGatewayName(),
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            })
            ->do(function () use ($type, $method, $url, $data, $headers, $startTime, $paymentId) {
                $response = Http::withHeaders(array_merge(
                    $this->getHttpHeaders(),
                    $headers
                ))->{$method}($url, $data);

                $responseTime = $this->calculateResponseTime($startTime);
                $responseData = $response->json() ?? [];
                $responseHeaders = $response->headers();
                $statusCode = $response->status();

                $this->logHttpRequest(
                    type: $type,
                    method: $method,
                    url: $url,
                    requestData: $data,
                    responseData: $responseData,
                    responseHeaders: $responseHeaders,
                    statusCode: $statusCode,
                    responseTime: $responseTime,
                    paymentId: $paymentId
                );

                return [
                    'success' => $response->successful(),
                    'status_code' => $statusCode,
                    'data' => $responseData,
                    'response_time' => $responseTime,
                ];
            });
    }

    /**
     * Get default HTTP headers
     */
    protected function getHttpHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Calculate response time in milliseconds
     */
    protected function calculateResponseTime(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000, 2);
    }

    /**
     * Build full URL from base URL and endpoint
     */
    protected function buildUrl(string $baseUrl, string $endpoint): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Abstract methods that must be implemented by using class
     */
    abstract protected function logHttpRequest(
        PaymentLogTypeEnum $type,
        string $method,
        string $url,
        array $requestData,
        array $responseData,
        array $responseHeaders,
        int $statusCode,
        float $responseTime,
        ?int $paymentId = null,
        ?string $error = null
    ): void;

    abstract public function getGatewayName(): string;
}
