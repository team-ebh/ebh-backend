<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Create Destination Location Pipe
 *
 * Creates new destination location if ride type requires it
 */
readonly class CreateDestinationLocationPipe
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        // Create new destination location if ride type requires it and location data is provided
        if (
            $context->dto->rideTypeId->isRoundTripWithWait()
            && ! is_null($context->dto->destinationLatitude)
            && ! is_null($context->dto->destinationLongitude)
        ) {
            $context->dto->trip->loadMissing('locations');
            $maxSequence = $context->dto->trip->locations->max('sequence') ?? 1;

            $this->tripRepository->createDestinationLocation(
                $context->dto->trip,
                $context->dto->destinationLocationTitle,
                $context->dto->destinationLocationSubTitle,
                $context->dto->destinationLatitude,
                $context->dto->destinationLongitude,
                $maxSequence + 1
            );
        }

        return $next($context);
    }
}
