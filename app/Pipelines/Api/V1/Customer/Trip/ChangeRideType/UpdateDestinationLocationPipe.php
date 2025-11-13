<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Update Destination Location Pipe
 *
 * Updates trip destination location if new location data is provided
 */
class UpdateDestinationLocationPipe
{
    public function __construct(
        private readonly TripRepositoryInterface $tripRepository
    ) {}

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Update destination location if provided
        $this->tripRepository->updateDestinationLocation(
            $context->dto->trip,
            $context->dto->destinationLocationTitle,
            $context->dto->destinationLocationSubTitle,
            $context->dto->destinationLatitude,
            $context->dto->destinationLongitude
        );

        return $next($context);
    }
}
