<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Create Trip Pipe
 *
 * Creates the trip record in the database
 */
class CreateTripPipe
{
    public function __construct(
        protected TripRepositoryInterface $tripRepository,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        $context->trip = $this->tripRepository->createTrip(
            $context->dto,
            $context->originLocation,
            $context->destinationLocation,
            $context->baseFare,
            $context->estimatedPrice
        );

        return $next($context);
    }
}
