<?php

declare(strict_types=1);

namespace App\Jobs\Cache;

use App\Services\Cache\TripLocationCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateTripLocationsCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly ?int $tripId = null
    ) {}

    public function handle(TripLocationCacheService $service): void
    {
        if ($this->tripId) {
            Log::info('Starting trip locations cache update for specific trip', [
                'trip_id' => $this->tripId,
            ]);

            $stats = $service->updateTripLocations($this->tripId);
        } else {
            Log::info('Starting all trip locations cache update');

            $stats = $service->updateAllTripLocations();
        }

        Log::info('Trip locations cache update completed', $stats);
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        return ['cache', 'trip-locations', $this->tripId ? "trip:{$this->tripId}" : 'all'];
    }
}
