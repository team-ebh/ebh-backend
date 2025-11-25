<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\GetEstimatedArrivalTimeDTO;
use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Trip\RiderLocationNotAvailableException;
use App\Models\Rider;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Services\DistanceCalculationService;
use App\Services\Trip\TripActionService;

/**
 * Get Estimated Arrival Time Action
 *
 * Calculates the estimated arrival time from rider's current location to the next destination
 */
readonly class GetEstimatedArrivalTimeAction
{
    public function __construct(
        private TripActionService $tripActionService,
        private DistanceCalculationService $distanceCalculationService,
    ) {}

    /**
     * Execute the action
     *
     * @return array{estimated_arrival_seconds: int}
     *
     * @throws \Throwable
     */
    public function __invoke(GetEstimatedArrivalTimeDTO $dto): array
    {
        $acceptedTripRequest = $this->getAcceptedTripRequest($dto);
        $rider = $this->getRiderWithLocation($acceptedTripRequest);
        $currentLocation = $this->getCurrentActiveLocation($dto);

        $estimatedArrivalSeconds = $this->calculateEstimatedArrivalTime(
            $rider,
            $currentLocation
        );

        return [
            'estimated_arrival_seconds' => $estimatedArrivalSeconds,
        ];
    }

    /**
     * Get accepted trip request for the trip
     *
     * @throws InvalidTripActionException
     * @throws \Throwable
     */
    private function getAcceptedTripRequest(GetEstimatedArrivalTimeDTO $dto): TripRequest
    {
        $acceptedTripRequest = $dto->trip->acceptedTripRequest;

        throw_if(
            ! $acceptedTripRequest,
            InvalidTripActionException::class
        );

        return $acceptedTripRequest;
    }

    /**
     * Get rider with valid location
     *
     * @throws RiderLocationNotAvailableException
     * @throws \Throwable
     */
    private function getRiderWithLocation(TripRequest $tripRequest): Rider
    {
        $rider = $tripRequest->rider;

        throw_if(
            ! $rider || ! $rider->{Rider::COLUMN_LATITUDE} || ! $rider->{Rider::COLUMN_LONGITUDE},
            RiderLocationNotAvailableException::class
        );

        return $rider;
    }

    /**
     * Get current active location that rider needs to reach
     *
     * @throws InvalidTripActionException
     * @throws \Throwable
     */
    private function getCurrentActiveLocation(GetEstimatedArrivalTimeDTO $dto): TripLocation
    {
        $currentLocation = $this->tripActionService->getCurrentLocation($dto->trip);

        throw_if(
            ! $currentLocation,
            InvalidTripActionException::class
        );

        return $currentLocation;
    }

    /**
     * Calculate estimated arrival time from rider location to destination
     */
    private function calculateEstimatedArrivalTime(Rider $rider, TripLocation $destination): int
    {
        $result = $this->distanceCalculationService->calculateDistanceAndDuration(
            originLatitude: (float) $rider->{Rider::COLUMN_LATITUDE},
            originLongitude: (float) $rider->{Rider::COLUMN_LONGITUDE},
            destinationLatitude: (float) $destination->{TripLocation::COLUMN_LATITUDE},
            destinationLongitude: (float) $destination->{TripLocation::COLUMN_LONGITUDE}
        );

        return $result['estimated_arrival_seconds'] ?? 0;
    }
}
