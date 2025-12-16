<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\CreateCustomerTokenDTO;
use App\Services\Payment\DTOs\CreatePaymentLinkDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;

/**
 * Payment Service
 *
 * Facade service for payment operations using Strategy Pattern
 * Clean implementation with Dependency Injection
 */
class PaymentService
{
    private PaymentGatewayInterface $gateway;

    /**
     * Constructor with Dependency Injection
     *
     * Uses Factory pattern to resolve gateway
     */
    public function __construct(
        private readonly PaymentGatewayFactory $factory
    ) {
        $this->gateway = $this->factory->create();
    }

    /**
     * Create payment link
     */
    public function createPaymentLink(CreatePaymentLinkDTO $dto): PaymentResponseDTO
    {
        return $this->gateway->createPaymentLink($dto);
    }

    /**
     * Create customer unique token
     */
    public function createCustomerToken(CreateCustomerTokenDTO $dto): PaymentResponseDTO
    {
        return $this->gateway->createCustomerToken($dto);
    }

    /**
     * Verify payment status
     *
     * @param  int  $paymentId  Internal payment ID
     * @param  string  $gatewayReferenceId  Gateway reference ID (trackId from UPayments)
     */
    public function verifyPayment(int $paymentId, string $gatewayReferenceId): PaymentResponseDTO
    {
        return $this->gateway->verifyPayment($paymentId, $gatewayReferenceId);
    }

    /**
     * Get current gateway name
     */
    public function getGatewayName(): string
    {
        return $this->gateway->getGatewayName();
    }

    /**
     * Get gateway configuration
     */
    public function getConfiguration(): array
    {
        return $this->gateway->getConfiguration();
    }

    /**
     * Switch to a different gateway dynamically
     *
     * Useful for multi-gateway scenarios
     */
    public function switchGateway(string $gatewayName): self
    {
        $this->gateway = $this->factory->create($gatewayName);

        return $this;
    }

    /**
     * Get list of all registered gateways
     *
     * @return array<string>
     */
    public function getAvailableGateways(): array
    {
        return $this->factory->getRegisteredGateways();
    }
}
