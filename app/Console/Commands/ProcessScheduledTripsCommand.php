<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Setting\SettingEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Models\Setting;
use App\Models\Trip;
use App\Services\Trip\ScheduledTripDispatcherService;
use Illuminate\Console\Command;

/**
 * Process Scheduled Trips Command
 *
 * Safety net command that runs periodically to catch any scheduled trips
 * that may have been missed (e.g., due to failed jobs, server restarts, etc.)
 *
 * This command should run every 5 minutes via Laravel Scheduler
 */
class ProcessScheduledTripsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'trips:process-scheduled';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled trips that are due (safety net for missed jobs)';

    public function __construct(
        private readonly ScheduledTripDispatcherService $dispatcherService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for scheduled trips to process...');

        // Get the configured minutes before scheduled time to start searching
        $searchStartMinutes = (int) Setting::get(SettingEnum::SCHEDULED_TRIP_SEARCH_START_MINUTES);

        // Find scheduled trips that are:
        // - Status is DRAFT (not cancelled, not already processed)
        // - Trip type is SCHEDULED
        // - Scheduled time is within X minutes from now (configurable)
        // - Has an order_id (confirmed and paid)
        $trips = Trip::query()
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::DRAFT)
            ->where(Trip::COLUMN_TRIP_TYPE_ID, TripTypeEnum::SCHEDULED->value)
            ->whereNotNull(Trip::COLUMN_ORDER_ID)
            ->where(Trip::COLUMN_SCHEDULED_TIME, '<=', now()->addMinutes($searchStartMinutes))
            ->get();

        if ($trips->isEmpty()) {
            $this->info('No scheduled trips to process.');

            return self::SUCCESS;
        }

        $this->info("Found {$trips->count()} scheduled trip(s) to process.");

        foreach ($trips as $trip) {
            $this->info("Dispatching job for trip #{$trip->{Trip::COLUMN_ID}}");

            // Dispatch job (will run immediately since scheduled time is already due)
            $this->dispatcherService->dispatch($trip);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
