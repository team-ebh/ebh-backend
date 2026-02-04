<?php

declare(strict_types=1);

namespace App\Jobs\Cache;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAllCachesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Log::info('Starting comprehensive cache update for all entities');

        // Dispatch individual cache update jobs
        UpdateRidersCacheJob::dispatch(onlineOnly: false);
        UpdateTripsCacheJob::dispatch(activeOnly: true);
        UpdateTripLocationsCacheJob::dispatch();
        UpdateAppStateCacheJob::dispatch(type: 'all');

        Log::info('All cache update jobs dispatched successfully');
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        return ['cache', 'update-all'];
    }
}
