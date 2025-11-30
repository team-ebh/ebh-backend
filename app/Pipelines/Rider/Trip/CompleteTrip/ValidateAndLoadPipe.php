<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CompleteTrip;

use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Exceptions\Rider\TripNotInProgressException;
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
     *
     * @throws \Throwable
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];

        // Load trip with locations
        $trip = $dto->tripRequest->load('trip')->trip;
        $payload['trip'] = $trip;

        // Validate trip is in progress
        throw_if(
            $trip->isCompleted(),
            TripNotInProgressException::class
        );

        // Get current location
        $currentLocation = $this->tripActionService->getCurrentLocation($trip);
        $payload['currentLocation'] = $currentLocation;

        // Validate current location exists
        throw_if(
            is_null($currentLocation),
            TripNotInProgressException::class
        );

        // Get last location
        $lastLocation = $this->tripActionService->getLastLocation($trip);
        $payload['lastLocation'] = $lastLocation;
        $payload['isLastLocation'] = $this->tripActionService->isSameLocation($currentLocation, $lastLocation);

        // Validate trip request belongs to rider
        throw_if(
            ! $dto->tripRequest->belongsToRider($dto->riderId),
            TripNotBelongToRiderException::class
        );

        throw_if(
            ! $dto->tripRequest->isAccepted() || $trip->isDraft(),
            InvalidTripActionException::class
        );

        // Validate can complete
        throw_if(
            ! $this->tripActionService->validateCanComplete($currentLocation),
            InvalidTripActionException::class
        );

        return $next($payload);
    }
}
