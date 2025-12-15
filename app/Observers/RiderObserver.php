<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Rider;
use App\Models\RiderStatusLog;

class RiderObserver
{
    /**
     * Handle the Rider "created" event.
     * Log the initial status when rider is first created.
     */
    public function created(Rider $rider): void
    {
        $this->logStatusChange($rider, $rider->{Rider::COLUMN_STATUS});
    }

    /**
     * Handle the Rider "updated" event.
     * Log status changes when rider is updated.
     */
    public function updated(Rider $rider): void
    {
        if ($rider->wasChanged(Rider::COLUMN_STATUS)) {
            $this->logStatusChange($rider, $rider->{Rider::COLUMN_STATUS});
        }
    }

    /**
     * Create a status log entry for the rider.
     */
    private function logStatusChange(Rider $rider, mixed $status): void
    {
        $changedBy = getAuthenticatedUser();

        RiderStatusLog::query()->create([
            RiderStatusLog::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
            RiderStatusLog::COLUMN_STATUS => $status,
            RiderStatusLog::COLUMN_CHANGED_BY_TYPE => $changedBy?->getMorphClass(),
            RiderStatusLog::COLUMN_CHANGED_BY_ID => $changedBy?->id,
        ]);
    }
}
