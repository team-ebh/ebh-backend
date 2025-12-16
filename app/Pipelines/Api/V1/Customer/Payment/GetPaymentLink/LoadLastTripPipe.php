<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Exceptions\Trip\TripNotFoundException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Load Active Trip Pipe
 *
 * Loads customer's active trip and creates context
 */
readonly class LoadLastTripPipe
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    /**
     * @throws TripNotFoundException
     * @throws \Throwable
     */
    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        $customerId = getAuthenticatedUser()->id;

        $trip = $this->tripRepository->getLastTrip($customerId);

        throw_if(
            ! $trip,
            TripNotFoundException::class,
        );

        // Eager load relationships needed for payment
        $trip->load(['customer', 'paidPayment']);

        $context->trip = $trip;

        return $next($context);
    }
}
