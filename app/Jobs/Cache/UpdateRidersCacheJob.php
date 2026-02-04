<?php

declare(strict_types=1);

namespace App\Jobs\Cache;

use App\Services\Cache\RiderCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateRidersCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly bool $onlineOnly = false
    ) {}

    public function handle(RiderCacheService $service): void
    {
        Log::info('Starting riders cache update', [
            'online_only' => $this->onlineOnly,
        ]);

        $stats = $this->onlineOnly
            ? $service->updateOnlineRiders()
            : $service->updateAllRiders();

        Log::info('Riders cache update completed', $stats);
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        return ['cache', 'riders', $this->onlineOnly ? 'online' : 'all'];
    }
}
