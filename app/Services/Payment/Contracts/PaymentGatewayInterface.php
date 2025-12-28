<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTOs\CreatePaymentLinkDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;

/**
 * Payment Gateway Interface
 *
 * Contract for all payment gateway implementations
 */
interface PaymentGatewayInterface
{
    /**
     * Create payment link
     */
    public function createPaymentLink(CreatePaymentLinkDTO $dto): PaymentResponseDTO;

    /**
     * Verify payment status
     *
     * @param  int  $paymentId  Internal payment ID
     * @param  string  $gatewayReferenceId  Gateway reference ID (trackId from UPayments)
     */
    public function verifyPayment(int $paymentId, string $gatewayReferenceId): PaymentResponseDTO;

    /**
     * Get gateway name
     */
    public function getGatewayName(): string;

    /**
     * Get gateway configuration
     */
    public function getConfiguration(): array;
}
