<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripLocationStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trip Location Aggregate Trait
 *
 * Contains all relationship methods for the TripLocation model
 */
trait TripLocationAggregate
{
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, TripLocation::COLUMN_TRIP_ID);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TripLocationStatusLog::class, TripLocationStatusLog::COLUMN_TRIP_LOCATION_ID)
            ->orderBy('created_at', 'desc');
    }
}
