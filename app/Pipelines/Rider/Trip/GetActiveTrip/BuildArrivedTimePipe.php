<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\GetActiveTrip;

use App\DTOs\Api\V1\Rider\Trip\GetEstimatedArrivalTimeDTO;
use App\Models\TripRequest;
use App\Pipelines\Rider\Trip\GetEstimatedArrivalTime\ValidateAndLoadPipe;
use App\Pipelines\Shared\Trip\GetEstimatedArrivalTime\CalculateEstimatedTimePipe;
use Closure;
use Illuminate\Pipeline\Pipeline;

/**
 * Build Arrived Time Pipe
 *
 * Calculates estimated arrival time if trip is in ACCEPTED_RIDER or ON_TRIP status
 */
class BuildArrivedTimePipe
{
    public function handle(array $payload, Closure $next): mixed
    {
        $activeTrip = $payload['activeTrip'];
        $tripRequest = $payload['tripRequest'];

        // Only calculate estimated arrival time for trips that are in progress
        if ($activeTrip->isAcceptedByRider() || $activeTrip->isOnTrip()) {
            $payload['arrived_time'] = safeProcess()
                ->onFailed(fn ($e) => null)  // If calculation fails (no rider location, etc.), return null
                ->do(function () use ($tripRequest) {
                    $dto = new GetEstimatedArrivalTimeDTO();
                    $dto->tripRequest = $tripRequest;
                    $dto->riderId = $tripRequest->{TripRequest::COLUMN_RIDER_ID};

                    $result = app(Pipeline::class)
                        ->send(['dto' => $dto])
                        ->through([
                            ValidateAndLoadPipe::class,
                            CalculateEstimatedTimePipe::class,
                        ])
                        ->thenReturn();

                    return $result['result']['estimated_arrival_seconds'];
                });
        } else {
            $payload['arrived_time'] = null;
        }

        return $next($payload);
    }
}
