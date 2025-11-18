<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Trip;
use App\Models\TripStatusLog;
use Illuminate\Support\Facades\Auth;

class TripObserver
{
    /**
     * Handle the Trip "created" event.
     * Log the initial status when trip is first created.
     */
    public function created(Trip $trip): void
    {
        $this->logStatusChange($trip, $trip->{Trip::COLUMN_STATUS});
    }

    /**
     * Handle the Trip "updating" event.
     * Log status changes when trip is updated.
     */
    public function updating(Trip $trip): void
    {
        if ($trip->isDirty(Trip::COLUMN_STATUS)) {
            $this->logStatusChange($trip, $trip->{Trip::COLUMN_STATUS});
        }
    }

    /**
     * Create a status log entry for the trip.
     */
    private function logStatusChange(Trip $trip, mixed $status): void
    {
        $changedBy = $this->getAuthenticatedUser();

        TripStatusLog::query()->create([
            TripStatusLog::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripStatusLog::COLUMN_STATUS => $status,
            TripStatusLog::COLUMN_CHANGED_BY_TYPE => $changedBy?->getMorphClass(),
            TripStatusLog::COLUMN_CHANGED_BY_ID => $changedBy?->id,
        ]);
    }

    /**
     * Get the currently authenticated user from any guard.
     * Checks in order: customer, rider, admin (web).
     */
    private function getAuthenticatedUser(): mixed
    {
        return Auth::guard('customer')->user()
            ?? Auth::guard('rider')->user()
            ?? Auth::guard('web')->user();
    }
}
