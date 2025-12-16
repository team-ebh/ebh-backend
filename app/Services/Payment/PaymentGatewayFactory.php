<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

/**
 * Payment Gateway Factory
 *
 * Factory pattern implementation for creating payment gateway instances
 * Follows Open/Closed Principle - easy to add new gateways without modification
 */
class PaymentGatewayFactory
{
    /**
     * Registry of available payment gateways
     *
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    private array $gateways = [];

    /**
     * Register a payment gateway
     *
     * @param  string  $name  Gateway identifier (e.g., 'upayments')
     * @param  class-string<PaymentGatewayInterface>  $class  Gateway class name
     */
    public function register(string $name, string $class): void
    {
        if (! is_subclass_of($class, PaymentGatewayInterface::class)) {
            throw new InvalidArgumentException(
                'Gateway class must implement PaymentGatewayInterface'
            );
        }

        $this->gateways[$name] = $class;
    }

    /**
     * Create a payment gateway instance
     *
     * @throws InvalidArgumentException
     */
    public function create(?string $gatewayName = null): PaymentGatewayInterface
    {
        $gatewayName = $gatewayName ?? $this->getDefaultGateway();

        if (! isset($this->gateways[$gatewayName])) {
            throw new InvalidArgumentException(
                "Payment gateway '{$gatewayName}' is not registered. Available gateways: " .
                implode(', ', array_keys($this->gateways))
            );
        }

        return app($this->gateways[$gatewayName]);
    }

    /**
     * Get list of registered gateways
     *
     * @return array<string>
     */
    public function getRegisteredGateways(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Check if gateway is registered
     */
    public function hasGateway(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * Get default gateway from configuration
     */
    private function getDefaultGateway(): string
    {
        return config('payment.default_gateway', 'upayments');
    }
}
