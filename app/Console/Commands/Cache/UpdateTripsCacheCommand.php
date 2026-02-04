<?php

declare(strict_types=1);

namespace App\Console\Commands\Cache;

use App\Jobs\Cache\UpdateTripsCacheJob;
use App\Services\Cache\TripCacheService;
use Illuminate\Console\Command;

class UpdateTripsCacheCommand extends Command
{
    protected $signature = 'cache:update-trips
                            {--all : Update all trips including completed}
                            {--async : Run update in background job}';

    protected $description = 'Update trips cache with current data from database';

    public function handle(TripCacheService $service): int
    {
        $all = $this->option('all');
        $async = $this->option('async');

        if ($async) {
            UpdateTripsCacheJob::dispatch(activeOnly: ! $all);
            $this->info('Trips cache update job dispatched to background queue');

            return self::SUCCESS;
        }

        $this->info('Updating trips cache...');

        $stats = $all
            ? $service->updateAllTrips()
            : $service->updateActiveTrips();

        $this->newLine();
        $this->info('✓ Trips cache updated successfully!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $stats['total']],
                ['Successfully Updated', $stats['success']],
                ['Failed', $stats['failed']],
            ]
        );

        return self::SUCCESS;
    }
}
