<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\ProcessPayment;

use App\Models\Payment;
use Closure;

/**
 * Generate Deeplink Pipe
 *
 * Generates deeplink for callback responses (not used for webhooks)
 */
readonly class GenerateDeeplinkPipe
{
    /**
     * Base deeplink URL from config
     */
    private string $baseDeeplink;

    public function __construct()
    {
        $this->baseDeeplink = config('payment.deeplink_base_url', 'ebhapp://payment');
    }

    public function handle(PaymentProcessContext $context, Closure $next): mixed
    {
        // Only generate deeplink for callbacks
        if (! $context->dto->isCallback) {
            return $next($context);
        }

        $context->deeplink = $context->payment->status->buildDeeplink(
            $this->baseDeeplink,
            $context->payment->{Payment::COLUMN_TRIP_ID},
            $context->payment->{Payment::COLUMN_PAYMENT_NUMBER}
        );

        return $next($context);
    }
}
