<?php

declare(strict_types=1);

namespace App\Services\Payment\Traits;

use App\Enums\Payment\PaymentLogTypeEnum;
use App\Services\Payment\DTOs\PaymentLogDTO;

/**
 * Logs Payment Requests Trait
 *
 * Reusable logging logic for payment gateway requests/responses
 * Single Responsibility: Logging and data sanitization
 */
trait LogsPaymentRequests
{
    /**
     * Log HTTP request/response to payment gateway
     */
    protected function logHttpRequest(
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
    ): void {
        $dto = new PaymentLogDTO(
            type: $type,
            method: $method,
            url: $url,
            requestHeaders: $this->sanitizeHeaders($this->getHttpHeaders()),
            requestBody: $this->sanitizeRequestData($requestData),
            responseHeaders: $responseHeaders,
            responseBody: $responseData,
            statusCode: $statusCode,
            responseTime: $responseTime,
            paymentId: $paymentId,
            error: $error
        );

        $this->getPaymentLogRepository()->create($dto);
    }

    /**
     * Sanitize sensitive data from headers
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveKeys = ['authorization', 'api-key', 'api-secret', 'token', 'x-api-key'];

        foreach ($sensitiveKeys as $key) {
            $lowerKey = strtolower($key);

            foreach ($headers as $headerKey => $value) {
                if (strtolower($headerKey) === $lowerKey) {
                    $headers[$headerKey] = '***REDACTED***';
                }
            }
        }

        return $headers;
    }

    /**
     * Sanitize sensitive data from request payload
     */
    protected function sanitizeRequestData(array $data): array
    {
        return $this->sanitizeRecursive($data);
    }

    /**
     * Recursively sanitize sensitive data
     */
    private function sanitizeRecursive(array $data): array
    {
        $sensitiveKeys = [
            'card_number',
            'cardNumber',
            'cvv',
            'cvc',
            'password',
            'pin',
            'card',
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeRecursive($value);

                continue;
            }

            if (in_array($key, $sensitiveKeys, true)) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Abstract methods that must be implemented by using class
     */
    abstract protected function getHttpHeaders(): array;

    abstract protected function getPaymentLogRepository();
}
