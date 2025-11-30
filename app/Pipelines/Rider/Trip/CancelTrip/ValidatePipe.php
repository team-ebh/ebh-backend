<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CancelTrip;

use App\Exceptions\Rider\TripCannotBeCancelledByRiderException;
use App\Exceptions\Rider\TripRequestNotBelongToRiderException;
use Closure;

class ValidatePipe
{
    /**
     * Handle the pipeline
     *
     * @throws \Throwable
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];
        $tripRequest = $dto->tripRequest;

        // Verify trip request belongs to this rider
        throw_if(
            ! $tripRequest->belongsToRider($dto->riderId),
            TripRequestNotBelongToRiderException::class
        );

        // Verify trip request is accepted
        throw_if(
            ! $tripRequest->isAccepted(),
            TripCannotBeCancelledByRiderException::class
        );

        // Verify trip status is cancellable by rider
        throw_if(
            ! $tripRequest->trip->canBeCancelledByRider(),
            TripCannotBeCancelledByRiderException::class
        );

        return $next($payload);
    }
}
