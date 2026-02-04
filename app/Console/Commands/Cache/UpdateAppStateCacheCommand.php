<?php

declare(strict_types=1);

namespace App\Console\Commands\Cache;

use App\Jobs\Cache\UpdateAppStateCacheJob;
use App\Services\Cache\AppStateCacheService;
use Illuminate\Console\Command;

class UpdateAppStateCacheCommand extends Command
{
    protected $signature = 'cache:update-app-state
                            {--type=all : Type to update (all, settings, vehicle_settings)}
                            {--async : Run update in background job}';

    protected $description = 'Update app state cache (settings and vehicle settings)';

    public function handle(AppStateCacheService $service): int
    {
        $type = $this->option('type');
        $async = $this->option('async');

        if (! in_array($type, ['all', 'settings', 'vehicle_settings'])) {
            $this->error('Invalid type. Must be one of: all, settings, vehicle_settings');

            return self::FAILURE;
        }

        if ($async) {
            UpdateAppStateCacheJob::dispatch($type);
            $this->info('App state cache update job dispatched to background queue');

            return self::SUCCESS;
        }

        $this->info('Updating app state cache...');

        $stats = match ($type) {
            'settings' => ['settings' => $service->updateSettings()],
            'vehicle_settings' => ['vehicle_settings' => $service->updateVehicleSettings()],
            default => $service->updateAll(),
        };

        $this->newLine();
        $this->info('✓ App state cache updated successfully!');

        if ($type === 'all') {
            $this->table(
                ['Entity', 'Total', 'Success', 'Failed'],
                [
                    ['Settings', $stats['settings']['total'], $stats['settings']['success'], $stats['settings']['failed']],
                    ['Vehicle Settings', $stats['vehicle_settings']['total'], $stats['vehicle_settings']['success'], $stats['vehicle_settings']['failed']],
                ]
            );
        } else {
            $entityStats = $stats[$type] ?? $stats;
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Processed', $entityStats['total']],
                    ['Successfully Updated', $entityStats['success']],
                    ['Failed', $entityStats['failed']],
                ]
            );
        }

        return self::SUCCESS;
    }
}
