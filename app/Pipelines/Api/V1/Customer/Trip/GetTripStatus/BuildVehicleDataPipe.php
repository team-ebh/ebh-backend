<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\Models\Vehicle;
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

        $vehicle = $context->trip->rider?->vehicle;

        if (! $vehicle) {
            $context->vehicle = null;

            return $next($context);
        }

        $context->vehicle = [
            'model' => $vehicle->getModelCar(),
            'plate_number' => $vehicle->{Vehicle::COLUMN_PLATE_NUMBER},
        ];

        return $next($context);
    }
}
