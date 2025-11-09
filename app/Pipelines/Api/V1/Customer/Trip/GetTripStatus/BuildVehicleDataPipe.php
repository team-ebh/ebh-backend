<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use Closure;

/**
 * Build Vehicle Data Pipe
 *
 * Builds vehicle information for the response
 */
class BuildVehicleDataPipe
{
    public function handle(TripStatusContext $context, Closure $next): mixed
    {
        if (! $context->found) {
            return $next($context);
        }

        // TODO: Replace with actual vehicle data from database

        $context->vehicle = [
            'model' => 'Toyota Camry',
            'plate_number' => '12345',
        ];

        return $next($context);
    }
}
