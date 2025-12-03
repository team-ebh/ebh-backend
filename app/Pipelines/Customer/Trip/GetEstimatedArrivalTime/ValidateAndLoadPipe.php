<?php

declare(strict_types=1);

namespace App\Pipelines\Customer\Trip\GetEstimatedArrivalTime;

use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Trip\RiderLocationNotAvailableException;
use App\Models\Rider;
use App\Services\Trip\TripActionService;
use Closure;

readonly class ValidateAndLoadPipe
{
    public function __construct(
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
        $trip = $dto->trip;

        // Validate trip status (must be ACCEPTED_RIDER or ON_TRIP)
        throw_if(
            ! $trip->isAcceptedByRider() && ! $trip->isOnTrip(),
            InvalidTripActionException::class
        );

        // Get accepted trip request for the trip
        $acceptedTripRequest = $trip->loadMissing('acceptedTripRequest')->acceptedTripRequest;

        throw_if(
            ! $acceptedTripRequest,
            InvalidTripActionException::class
        );

        // Get rider with valid location
        $rider = $acceptedTripRequest->rider;

        // TODO:: must be read from redis service
        throw_if(
            ! $rider || ! $rider->{Rider::COLUMN_LATITUDE} || ! $rider->{Rider::COLUMN_LONGITUDE},
            RiderLocationNotAvailableException::class
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
