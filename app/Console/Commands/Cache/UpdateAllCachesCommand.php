<?php

declare(strict_types=1);

namespace App\Console\Commands\Cache;

use App\Jobs\Cache\UpdateAllCachesJob;
use App\Services\Cache\AppStateCacheService;
use App\Services\Cache\RiderCacheService;
use App\Services\Cache\TripCacheService;
use App\Services\Cache\TripLocationCacheService;
use Illuminate\Console\Command;

class UpdateAllCachesCommand extends Command
{
    protected $signature = 'cache:update-all
                            {--async : Run updates in background jobs}';

    protected $description = 'Update all caches (riders, trips, trip locations) with current data';

    public function handle(
        RiderCacheService $riderService,
        TripCacheService $tripService,
        TripLocationCacheService $locationService,
        AppStateCacheService $appStateService
    ): int {
        $async = $this->option('async');

        if ($async) {
            UpdateAllCachesJob::dispatch();
            $this->info('All cache update jobs dispatched to background queue');

            return self::SUCCESS;
        }

        $this->info('Starting comprehensive cache update...');
        $this->newLine();

        // Update app state cache
        $this->info('1/4 Updating app state cache...');
        $appStateStats = $appStateService->updateAll();
        $this->line("   ✓ App State: {$appStateStats['success']}/{$appStateStats['total']} updated");

        // Update riders cache
        $this->info('2/4 Updating riders cache...');
        $riderStats = $riderService->updateAllRiders();
        $this->line("   ✓ Riders: {$riderStats['success']}/{$riderStats['total']} updated");

        // Update trips cache
        $this->info('3/4 Updating trips cache...');
        $tripStats = $tripService->updateActiveTrips();
        $this->line("   ✓ Trips: {$tripStats['success']}/{$tripStats['total']} updated");

        // Update trip locations cache
        $this->info('4/4 Updating trip locations cache...');
        $locationStats = $locationService->updateAllTripLocations();
        $this->line("   ✓ Trip Locations: {$locationStats['success']}/{$locationStats['total']} updated");

        $this->newLine();
        $this->info('✓ All caches updated successfully!');
        $this->newLine();

        $this->table(
            ['Entity', 'Total', 'Success', 'Failed'],
            [
                ['App State', $appStateStats['total'], $appStateStats['success'], $appStateStats['failed']],
                ['Riders', $riderStats['total'], $riderStats['success'], $riderStats['failed']],
                ['Trips', $tripStats['total'], $tripStats['success'], $tripStats['failed']],
                ['Trip Locations', $locationStats['total'], $locationStats['success'], $locationStats['failed']],
            ]
        );

        return self::SUCCESS;
    }
}
