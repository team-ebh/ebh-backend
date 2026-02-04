<?php

declare(strict_types=1);

namespace App\Console\Commands\Cache;

use App\Jobs\Cache\UpdateRidersCacheJob;
use App\Services\Cache\RiderCacheService;
use Illuminate\Console\Command;

class UpdateRidersCacheCommand extends Command
{
    protected $signature = 'cache:update-riders
                            {--online : Only update online riders}
                            {--async : Run update in background job}';

    protected $description = 'Update riders cache with current data from database';

    public function handle(RiderCacheService $service): int
    {
        $onlineOnly = $this->option('online');
        $async = $this->option('async');

        if ($async) {
            UpdateRidersCacheJob::dispatch($onlineOnly);
            $this->info('Riders cache update job dispatched to background queue');

            return self::SUCCESS;
        }

        $this->info('Updating riders cache...');

        $stats = $onlineOnly
            ? $service->updateOnlineRiders()
            : $service->updateAllRiders();

        $this->newLine();
        $this->info('✓ Riders cache updated successfully!');
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
