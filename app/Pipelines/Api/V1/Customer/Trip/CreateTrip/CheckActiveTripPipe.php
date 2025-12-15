<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Exceptions\Trip\CustomerAlreadyHasActiveTripException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use Closure;

/**
 * Check Active Trip Pipe
 *
 * Checks if customer already has an active trip before creating a new one
 */
readonly class CheckActiveTripPipe
{
    public function __construct(
        private CustomerTripRepositoryInterface $customerTripRepository
    ) {}

    /**
     * @throws CustomerAlreadyHasActiveTripException
     * @throws \Throwable
     */
    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        throw_if(
            $this->customerTripRepository->existsActiveTrip($context->dto->customerId),
            CustomerAlreadyHasActiveTripException::class,
        );

        return $next($context);
    }
}
