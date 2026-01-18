<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Setting\SettingEnum;
use App\Jobs\ProcessScheduledTripJob;
use App\Models\Setting;
use App\Models\Trip;
use Illuminate\Support\Carbon;

/**
 * Scheduled Trip Dispatcher Service
 *
 * Handles dispatching scheduled trip jobs with proper delay calculation
 * based on the configured search start time setting.
 */
class ScheduledTripDispatcherService
{
    /**
     * Dispatch a scheduled trip job with calculated delay
     *
     * The job is dispatched to run X minutes before the scheduled time,
     * where X is configured via SCHEDULED_TRIP_SEARCH_START_MINUTES setting.
     */
    public function dispatch(Trip $trip, ?PaymentMethodEnum $paymentMethod = null): void
    {
        $scheduledTime = $trip->{Trip::COLUMN_SCHEDULED_TIME};

        if (! $scheduledTime) {
            return;
        }

        $delay = $this->calculateDelaySeconds($scheduledTime);

        ProcessScheduledTripJob::dispatch(
            tripId: $trip->{Trip::COLUMN_ID},
            paymentMethod: $paymentMethod
        )->delay(now()->addSeconds($delay));
    }

    /**
     * Calculate delay in seconds until the job should run
     *
     * The job should run X minutes before the scheduled time,
     * where X is the configured search start minutes setting.
     */
    public function calculateDelaySeconds(mixed $scheduledTime): int
    {
        $searchStartMinutes = (int) Setting::get(SettingEnum::SCHEDULED_TRIP_SEARCH_START_MINUTES);

        // Calculate when the job should run (X minutes before scheduled time)
        $jobRunTime = Carbon::parse($scheduledTime)->subMinutes($searchStartMinutes);

        // Calculate delay from now until job run time
        $delay = $jobRunTime->diffInSeconds(now(), false);

        // If job run time is in the past or very soon, dispatch immediately
        if ($delay <= 0) {
            return 0;
        }

        return (int) $delay;
    }
}
