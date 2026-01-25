<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Models\Trip;
use App\Services\Trip\ScheduledTripDispatcherService;
use Closure;

/**
 * Dispatch Scheduled Trip Job Pipe
 *
 * Dispatches a delayed job to process the scheduled trip at its scheduled time.
 * The job will change status to PENDING_RIDER and send rider requests.
 */
readonly class DispatchScheduledTripJobPipe
{
    public function __construct(
        private ScheduledTripDispatcherService $dispatcherService,
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        $trip = $context->dto->trip;
        $scheduledTime = $trip->{Trip::COLUMN_SCHEDULED_TIME};

        if (! $scheduledTime) {
            return $next($context);
        }

        $this->dispatcherService->dispatch(
            trip: $trip,
            paymentMethod: $context->dto->paymentMethod
        );

        return $next($context);
    }
}
