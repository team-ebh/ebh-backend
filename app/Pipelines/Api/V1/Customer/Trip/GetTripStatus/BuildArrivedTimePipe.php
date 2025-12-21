<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\DTOs\Api\V1\Customer\Trip\GetEstimatedArrivalTimeDTO;
use App\Pipelines\Customer\Trip\GetEstimatedArrivalTime\ValidateAndLoadPipe;
use App\Pipelines\Shared\Trip\GetEstimatedArrivalTime\CalculateEstimatedTimePipe;
use Closure;
use Illuminate\Pipeline\Pipeline;

/**
 * Build Arrived Time Pipe
 *
 * Calculates estimated arrival time if trip is in ACCEPTED_RIDER or IN_PROGRESS status
 */
class BuildArrivedTimePipe
{
    public function handle(TripStatusContext $context, Closure $next): mixed
    {
        if (! $context->found) {
            return $next($context);
        }

        // Only calculate estimated arrival time for trips that are in progress
        if ($context->trip->isAcceptedByRider() || $context->trip->isInProgress()) {
            $context->arrivedTime = safeProcess()
                ->onFailed(fn ($e) => null)  // If calculation fails (no rider location, etc.), return null
                ->do(function () use ($context) {
                    $dto = new GetEstimatedArrivalTimeDTO();
                    $dto->trip = $context->trip;

                    $result = app(Pipeline::class)
                        ->send(['dto' => $dto])
                        ->through([
                            ValidateAndLoadPipe::class,
                            CalculateEstimatedTimePipe::class,
                        ])
                        ->thenReturn();

                    return $result['result']['estimated_arrival_seconds'];
                });
        }

        return $next($context);
    }
}
