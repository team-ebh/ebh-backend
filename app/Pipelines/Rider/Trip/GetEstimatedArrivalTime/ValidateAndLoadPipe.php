<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\GetEstimatedArrivalTime;

use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Rider\LocationNotAvailableException;
use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;
use App\Services\Trip\TripActionService;
use Closure;

readonly class ValidateAndLoadPipe
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
        private TripActionService $tripActionService,
    ) {}

    /**
     * Handle the pipeline
     *
     * @throws \Throwable
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];
        $tripRequest = $dto->tripRequest;
        $trip = $tripRequest->trip;

        // Validate trip request belongs to rider and is accepted
        throw_if(
            ! $tripRequest->belongsToRider($dto->riderId),
            TripNotBelongToRiderException::class
        );

        throw_if(
            ! $tripRequest->isAccepted(),
            InvalidTripActionException::class
        );

        // Get rider with valid location
        $rider = $this->riderRepository->find($dto->riderId);

        throw_if(
            ! $rider || ! $rider->{Rider::COLUMN_LATITUDE} || ! $rider->{Rider::COLUMN_LONGITUDE},
            LocationNotAvailableException::class
        );

        // Get current active location that rider needs to reach
        $currentLocation = $this->tripActionService->getCurrentLocation($trip);

        throw_if(
            ! $currentLocation,
            InvalidTripActionException::class
        );

        $payload['rider'] = $rider;
        $payload['currentLocation'] = $currentLocation;

        return $next($payload);
    }
}
