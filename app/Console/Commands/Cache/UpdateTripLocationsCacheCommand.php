<?php

declare(strict_types=1);

namespace App\Console\Commands\Cache;

use App\Jobs\Cache\UpdateTripLocationsCacheJob;
use App\Services\Cache\TripLocationCacheService;
use Illuminate\Console\Command;

class UpdateTripLocationsCacheCommand extends Command
{
    protected $signature = 'cache:update-trip-locations
                            {--trip-id= : Update locations for specific trip only}
                            {--async : Run update in background job}';

    protected $description = 'Update trip locations cache with current data from database';

    public function handle(TripLocationCacheService $service): int
    {
        $tripId = $this->option('trip-id');
        $async = $this->option('async');

        if ($async) {
            UpdateTripLocationsCacheJob::dispatch($tripId ? (int) $tripId : null);
            $this->info('Trip locations cache update job dispatched to background queue');

            return self::SUCCESS;
        }

        $this->info('Updating trip locations cache...');

        if ($tripId) {
            $stats = $service->updateTripLocations((int) $tripId);
            $this->info("Updating locations for trip ID: {$tripId}");
        } else {
            $stats = $service->updateAllTripLocations();
        }

        $this->newLine();
        $this->info('✓ Trip locations cache updated successfully!');
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
