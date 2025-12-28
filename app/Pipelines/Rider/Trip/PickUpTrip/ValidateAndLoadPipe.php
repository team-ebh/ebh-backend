<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\PickUpTrip;

use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Rider\TripNotBelongToRiderException;
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

        // Load trip
        $trip = $dto->tripRequest->trip;
        $payload['trip'] = $trip;

        // Get current location
        $currentLocation = $this->tripActionService->getCurrentLocation($trip);
        $payload['currentLocation'] = $currentLocation;

        // Validate trip request belongs to rider
        throw_if(
            ! $dto->tripRequest->belongsToRider($dto->riderId),
            TripNotBelongToRiderException::class
        );

        // Validate trip is assigned to this rider
        throw_if(
            ! $trip->belongsToRider($dto->riderId),
            TripNotBelongToRiderException::class
        );

        throw_if(
            ! $dto->tripRequest->isAccepted(),
            InvalidTripActionException::class
        );

        // Validate can pick up
        throw_if(
            ! $this->tripActionService->validateCanPickUp($currentLocation),
            InvalidTripActionException::class
        );

        return $next($payload);
    }
}
