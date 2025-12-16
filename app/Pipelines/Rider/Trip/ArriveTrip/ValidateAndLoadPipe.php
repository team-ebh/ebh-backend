<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\ArriveTrip;

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
        $trip = $dto->tripRequest->load('trip')->trip;
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
            ! $dto->tripRequest->isAccepted() || $trip->isDraft(),
            InvalidTripActionException::class
        );

        // Validate can arrive
        throw_if(
            ! $this->tripActionService->validateCanArrive($currentLocation),
            InvalidTripActionException::class
        );

        return $next($payload);
    }
}
