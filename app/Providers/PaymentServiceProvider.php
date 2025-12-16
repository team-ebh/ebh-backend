<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Payment\Gateways\UPaymentsGateway;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Payment Service Provider
 *
 * Registers payment gateways and configures the factory
 * Follows Open/Closed Principle - add new gateways here
 */
class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register payment services
     */
    public function register(): void
    {
        // Register factory as singleton
        $this->app->singleton(PaymentGatewayFactory::class, function ($app) {
            $factory = new PaymentGatewayFactory;

            // Register available payment gateways
            $this->registerGateways($factory);

            return $factory;
        });
    }

    /**
     * Bootstrap payment services
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register payment gateways with factory
     *
     * To add a new gateway:
     * 1. Create gateway class implementing PaymentGatewayInterface
     * 2. Register it here using $factory->register()
     * 3. Add gateway configuration to config/payment.php
     */
    private function registerGateways(PaymentGatewayFactory $factory): void
    {
        // Register UPayments Gateway
        $factory->register('upayments', UPaymentsGateway::class);

        // Future gateways can be registered here:
        // $factory->register('myfatoorah', MyFatoorahGateway::class);
        // $factory->register('stripe', StripeGateway::class);
        // $factory->register('paypal', PayPalGateway::class);
    }
}
