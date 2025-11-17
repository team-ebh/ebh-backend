<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

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

        // TODO:: must be dynamic with trip table
        if ($context->trip->isAcceptedByRider() || $context->trip->isArrived() || $context->trip->isPickedUp()) {
            $context->arrivedTime = now()->addMinutes(5)->timestamp;
        }

        return $next($context);
    }
}
