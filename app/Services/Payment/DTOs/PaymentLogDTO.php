<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

/**
 * Payment Log DTO
 *
 * Data transfer object for payment request/response logging
 */
class PaymentLogDTO
{
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $requestHeaders,
        public readonly array $requestBody,
        public readonly array $responseHeaders,
        public readonly array $responseBody,
        public readonly int $statusCode,
        public readonly float $responseTime,
        public readonly ?int $paymentId = null,
        public readonly ?string $error = null,
    ) {}
}
