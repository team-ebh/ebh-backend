<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Exceptions\Trip\TripPaymentNotAllowedException;
use App\Interfaces\Repositories\Api\V1\Customer\OrderRepositoryInterface;
use Closure;

/**
 * Validate Payment Eligibility Pipe
 *
 * Validates if trip is eligible for payment:
 * - Payment method must be KNET
 * - Trip must be completed
 * - If trip has order, all trips in order must be completed
 * - Payment must not be already successful
 */
readonly class ValidatePaymentEligibilityPipe
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

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

        // If trip has an order, check if all trips in order are completed
        if ($context->trip->order_id) {
            throw_if(
                ! $this->orderRepository->areAllTripsCompleted($context->trip->order_id),
                TripPaymentNotAllowedException::class,
            );
        }

        throw_if(
            $context->trip->hasPaidPayment(),
            TripPaymentNotAllowedException::class,
        );

        return $next($context);
    }
}
