<?php

declare(strict_types=1);

namespace App\Jobs\Cache;

use App\Services\Cache\TripCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateTripsCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly bool $activeOnly = true
    ) {}

    public function handle(TripCacheService $service): void
    {
        Log::info('Starting trips cache update', [
            'active_only' => $this->activeOnly,
        ]);

        $stats = $this->activeOnly
            ? $service->updateActiveTrips()
            : $service->updateAllTrips();

        Log::info('Trips cache update completed', $stats);
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        return ['cache', 'trips', $this->activeOnly ? 'active' : 'all'];
    }
}
