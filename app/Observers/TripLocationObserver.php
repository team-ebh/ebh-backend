<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TripLocation;
use App\Models\TripLocationStatusLog;
use Illuminate\Support\Facades\Log;

class TripLocationObserver
{
    /**
     * Handle the TripLocation "updated" event.
     */
    public function updated(TripLocation $tripLocation): void
    {
        // Check if status was changed
        if ($tripLocation->isDirty(TripLocation::COLUMN_STATUS)) {
            $this->logStatusChange($tripLocation);
        }
    }

    /**
     * Log status change to trip_location_status_logs table
     */
    private function logStatusChange(TripLocation $tripLocation): void
    {
        try {
            TripLocationStatusLog::query()
                ->create([
                    TripLocationStatusLog::COLUMN_TRIP_LOCATION_ID => $tripLocation->{TripLocation::COLUMN_ID},
                    TripLocationStatusLog::COLUMN_STATUS => $tripLocation->{TripLocation::COLUMN_STATUS},
                ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log trip location status change', [
                'trip_location_id' => $tripLocation->{TripLocation::COLUMN_ID},
                'error' => $e->getMessage(),
            ]);
        }
    }
}
