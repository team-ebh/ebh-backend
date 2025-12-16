<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Exceptions\Trip\TripPaymentNotAllowedException;
use Closure;

/**
 * Validate Payment Eligibility Pipe
 *
 * Validates if trip is eligible for payment:
 * - Payment method must be KNET
 * - Trip must be completed
 * - Payment must not be already successful
 */
readonly class ValidatePaymentEligibilityPipe
{
    /**
     * @throws TripPaymentNotAllowedException
     * @throws \Throwable
     */
    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        throw_if(
            ! $context->trip->isKnetPayment(),
            TripPaymentNotAllowedException::class,
        );

        throw_if(
            ! $context->trip->isCompleted(),
            TripPaymentNotAllowedException::class,
        );

        throw_if(
            ! is_null($context->trip->paidPayment),
            TripPaymentNotAllowedException::class,
        );

        return $next($context);
    }
}
