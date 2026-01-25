<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Exceptions\Trip\CustomerAlreadyHasActiveTripException;
use App\Exceptions\Trip\CustomerHasScheduledTripException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use Closure;

/**
 * Check Active Trip Pipe
 *
 * Checks if customer already has an active trip or scheduled trip before creating a new one
 */
readonly class CheckActiveTripPipe
{
    public function __construct(
        private CustomerTripRepositoryInterface $customerTripRepository
    ) {}

    /**
     * @throws CustomerAlreadyHasActiveTripException
     * @throws CustomerHasScheduledTripException
     * @throws \Throwable
     */
    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        // Check for active trips
        throw_if(
            $this->customerTripRepository->existsActiveTrip($context->dto->customerId),
            CustomerAlreadyHasActiveTripException::class,
        );

        // Check for scheduled trips (from ROUND_TRIP)
        throw_if(
            $this->customerTripRepository->existsScheduledTrip($context->dto->customerId),
            CustomerHasScheduledTripException::class,
        );

        return $next($context);
    }
}
