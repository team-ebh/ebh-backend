<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Enums\Trip\TripLocationTypeEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripLocation;
use Closure;

/**
 * Create Demand Trip Pipe
 *
 * Creates demand trip for ROUND_TRIP ride type
 */
readonly class CreateDemandTripPipe
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        // Create demand trip for ROUND_TRIP ride type
        if (
            $context->dto->rideTypeId->isRoundTrip()
            && ! is_null($context->dto->destinationLatitude)
            && ! is_null($context->dto->destinationLongitude)
        ) {
            // Get last destination location as origin for demand trip
            $context->dto->trip->loadMissing('locations');
            $lastDestination = $context->dto->trip->locations
                ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)
                ->sortByDesc(TripLocation::COLUMN_SEQUENCE)
                ->first();

            if ($lastDestination) {
                $originLocation = [
                    'title' => $lastDestination->{TripLocation::COLUMN_LOCATION_TITLE},
                    'sub_title' => $lastDestination->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                    'latitude' => (float) $lastDestination->{TripLocation::COLUMN_LATITUDE},
                    'longitude' => (float) $lastDestination->{TripLocation::COLUMN_LONGITUDE},
                ];

                $destinationLocation = [
                    'title' => $context->dto->destinationLocationTitle,
                    'sub_title' => $context->dto->destinationLocationSubTitle,
                    'latitude' => $context->dto->destinationLatitude,
                    'longitude' => $context->dto->destinationLongitude,
                ];

                $context->demandTrip = $this->tripRepository->createDemandTrip(
                    $context->dto->trip,
                    $originLocation,
                    $destinationLocation,
                    $context->dto->scheduledTime
                );
            }
        }

        return $next($context);
    }
}
