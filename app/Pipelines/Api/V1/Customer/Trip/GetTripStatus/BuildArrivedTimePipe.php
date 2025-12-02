<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\Models\TripRequest;
use Closure;

/**
 * Build Arrived Time Pipe
 *
 * Calculates arrived time timestamp if driver has arrived
 */
class BuildArrivedTimePipe
{
    public function handle(TripStatusContext $context, Closure $next): mixed
    {
        if (! $context->found) {
            return $next($context);
        }

        if ($context->trip->isAcceptedByRider() || $context->trip->isArrived() || $context->trip->isPickedUp()) {
            $context->arrivedTime = $context->trip
                ->load('acceptedTripRequest:id,trip_id,arrived_at')
                ->acceptedTripRequest
                ?->{TripRequest::COLUMN_ARRIVED_AT};
        }

        return $next($context);
    }
}
