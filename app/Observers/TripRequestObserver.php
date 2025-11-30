<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TripRequest;
use App\Models\TripRequestStatusLog;
use Illuminate\Support\Facades\Auth;

class TripRequestObserver
{
    /**
     * Handle the TripRequest "created" event.
     * Log the initial status when trip request is first created.
     */
    public function created(TripRequest $tripRequest): void
    {
        $this->logStatusChange($tripRequest, $tripRequest->{TripRequest::COLUMN_STATUS});
    }

    /**
     * Handle the TripRequest "updating" event.
     * Log status changes when trip request is updated.
     */
    public function updating(TripRequest $tripRequest): void
    {
        if ($tripRequest->isDirty(TripRequest::COLUMN_STATUS)) {
            $this->logStatusChange($tripRequest, $tripRequest->{TripRequest::COLUMN_STATUS});
        }
    }

    /**
     * Create a status log entry for the trip request.
     */
    private function logStatusChange(TripRequest $tripRequest, mixed $status): void
    {
        $changedBy = $this->getAuthenticatedUser();

        TripRequestStatusLog::query()->create([
            TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID => $tripRequest->{TripRequest::COLUMN_ID},
            TripRequestStatusLog::COLUMN_STATUS => $status,
            TripRequestStatusLog::COLUMN_CHANGED_BY_TYPE => $changedBy?->getMorphClass(),
            TripRequestStatusLog::COLUMN_CHANGED_BY_ID => $changedBy?->id,
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
