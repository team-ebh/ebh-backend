<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Trip;
use App\Models\TripStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Trip Status Log Aggregate Trait
 *
 * Contains all relationship methods for the TripStatusLog model
 */
trait TripStatusLogAggregate
{
    /**
     * Get the trip that owns the status log
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, TripStatusLog::COLUMN_TRIP_ID);
    }

    /**
     * Get the entity that changed the status (Admin, Rider, or Customer)
     */
    public function changedBy(): MorphTo
    {
        return $this->morphTo('changed_by');
    }
}
