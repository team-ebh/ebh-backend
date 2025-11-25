<?php

declare(strict_types=1);

namespace App\Jobs\Trip;

use App\Enums\Trip\TripRequestStatusEnum;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Lock Remaining Trip Requests Job
 *
 * Locks all pending trip requests for a trip after one has been accepted
 */
class LockRemainingTripRequestsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance
     */
    public function __construct(
        public int $tripId,
        public int $acceptedTripRequestId,
    ) {
        $this->afterCommit();
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        TripRequest::query()
            ->where(TripRequest::COLUMN_TRIP_ID, $this->tripId)
            ->where(TripRequest::COLUMN_ID, '!=', $this->acceptedTripRequestId)
            ->where(TripRequest::COLUMN_STATUS, TripRequestStatusEnum::PENDING)
            ->chunkById(20, function ($tripRequests) {
                foreach ($tripRequests as $tripRequest) {
                    $tripRequest->update([
                        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::LOCKED,
                        TripRequest::COLUMN_RESPONDED_AT => now(),
                    ]);
                }
            });
    }
}
