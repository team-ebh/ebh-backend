<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Models\Trip;
use App\Services\Trip\ScheduledTripDispatcherService;
use Closure;

/**
 * Dispatch Demand Trip Job Pipe
 *
 * Dispatches a delayed job to process the demand trip (return trip) at its scheduled time.
 * This applies to ROUND_TRIP ride types where a demand trip is created with a return_time.
 */
readonly class DispatchDemandTripJobPipe
{
    public function __construct(
        private ScheduledTripDispatcherService $dispatcherService,
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        // Only dispatch if a demand trip was created
        if (! isset($context->demandTrip)) {
            return $next($context);
        }

        $demandTrip = $context->demandTrip;
        $scheduledTime = $demandTrip->{Trip::COLUMN_SCHEDULED_TIME};

        // Only dispatch if the demand trip has a scheduled time
        if (! $scheduledTime) {
            return $next($context);
        }

        $this->dispatcherService->dispatch(
            trip: $demandTrip,
            paymentMethod: $context->dto->paymentMethod
        );

        return $next($context);
    }
}
