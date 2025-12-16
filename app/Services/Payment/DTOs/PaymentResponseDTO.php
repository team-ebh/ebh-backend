<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Interfaces\DTOs\ArrayDataTransferObject;

/**
 * Payment Response DTO
 *
 * Standardized response from payment gateway operations
 */
class PaymentResponseDTO implements ArrayDataTransferObject
{
    public bool $success;

    public int | string | null $paymentId = null;

    public ?string $gatewayTransactionId = null;

    public ?string $message = null;

    public ?string $errorCode = null;

    public ?array $rawResponse = null;

    public function getDataFromArray(array $data): void
    {
        $this->success = $data['success'];
        $this->paymentId = $data['transaction_id'] ?? null;
        $this->gatewayTransactionId = $data['track_id'] ?? null;
        $this->message = $data['message'] ?? null;
        $this->errorCode = $data['error_code'] ?? null;
        $this->rawResponse = $data['raw_response'] ?? null;
    }

    /**
     * Create success response
     */
    public static function success(
        int | string | null $paymentId = null,
        ?string $gatewayTransactionId = null,
        ?string $message = null,
        ?array $rawResponse = null
    ): self {
        $instance = new self();
        $instance->success = true;
        $instance->paymentId = $paymentId;
        $instance->gatewayTransactionId = $gatewayTransactionId;
        $instance->message = $message;
        $instance->rawResponse = $rawResponse;

        return $instance;
    }

    /**
     * Create error response
     */
    public static function error(
        string $message,
        ?string $errorCode = null,
        ?array $rawResponse = null
    ): self {
        $instance = new self();
        $instance->success = false;
        $instance->message = $message;
        $instance->errorCode = $errorCode;
        $instance->rawResponse = $rawResponse;

        return $instance;
    }
}
