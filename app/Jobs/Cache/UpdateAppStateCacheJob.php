<?php

declare(strict_types=1);

namespace App\Jobs\Cache;

use App\Services\Cache\AppStateCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAppStateCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $type = 'all' // 'all', 'settings', 'vehicle_settings'
    ) {}

    public function handle(AppStateCacheService $service): void
    {
        Log::info('Starting app state cache update', [
            'type' => $this->type,
        ]);

        $stats = match ($this->type) {
            'settings' => ['settings' => $service->updateSettings()],
            'vehicle_settings' => ['vehicle_settings' => $service->updateVehicleSettings()],
            default => $service->updateAll(),
        };

        Log::info('App state cache update completed', $stats);
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        return ['cache', 'app-state', $this->type];
    }
}
