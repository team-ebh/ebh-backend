<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
