<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\GetEstimatedArrivalTimeDTO;
use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Rider\LocationNotAvailableException;
use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
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
        private RiderRepositoryInterface $riderRepository,
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
        $tripRequest = $dto->tripRequest;
        $trip = $tripRequest->trip;

        $this->validateTripRequest($tripRequest, $dto->riderId);

        $rider = $this->getRiderWithLocation($dto->riderId);
        $currentLocation = $this->getCurrentActiveLocation($trip);

        $estimatedArrivalSeconds = $this->calculateEstimatedArrivalTime(
            $rider,
            $currentLocation
        );

        return [
            'estimated_arrival_seconds' => $estimatedArrivalSeconds,
        ];
    }

    /**
     * Validate trip request belongs to rider and is accepted
     *
     * @throws TripNotBelongToRiderException
     * @throws InvalidTripActionException
     * @throws \Throwable
     */
    private function validateTripRequest(object $tripRequest, int $riderId): void
    {
        throw_if(
            ! $tripRequest->belongsToRider($riderId),
            TripNotBelongToRiderException::class
        );

        throw_if(
            ! $tripRequest->isAccepted(),
            InvalidTripActionException::class
        );
    }

    /**
     * Get rider with valid location
     *
     * @throws LocationNotAvailableException
     * @throws \Throwable
     */
    private function getRiderWithLocation(int $riderId): Rider
    {
        $rider = $this->riderRepository->find($riderId);

        throw_if(
            ! $rider || ! $rider->{Rider::COLUMN_LATITUDE} || ! $rider->{Rider::COLUMN_LONGITUDE},
            LocationNotAvailableException::class
        );

        return $rider;
    }

    /**
     * Get current active location that rider needs to reach
     *
     * @throws InvalidTripActionException
     * @throws \Throwable
     */
    private function getCurrentActiveLocation(Trip $trip): TripLocation
    {
        $currentLocation = $this->tripActionService->getCurrentLocation($trip);

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
