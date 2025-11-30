<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\TripRequest;
use App\Models\TripRequestStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Trip Request Status Log Aggregate Trait
 *
 * Contains all relationship methods for the TripRequestStatusLog model
 */
trait TripRequestStatusLogAggregate
{
    /**
     * Get the trip request that owns the status log
     */
    public function tripRequest(): BelongsTo
    {
        return $this->belongsTo(TripRequest::class, TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID);
    }

    /**
     * Get the entity that changed the status (Admin, Rider, or Customer)
     */
    public function changedBy(): MorphTo
    {
        return $this->morphTo('changed_by');
    }
}
