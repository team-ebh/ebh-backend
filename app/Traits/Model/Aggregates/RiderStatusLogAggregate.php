<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Rider;
use App\Models\RiderStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Rider Status Log Aggregate Trait
 *
 * Contains all relationship methods for the RiderStatusLog model
 */
trait RiderStatusLogAggregate
{
    /**
     * Get the rider that owns the status log
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, RiderStatusLog::COLUMN_RIDER_ID);
    }

    /**
     * Get the entity that changed the status (Admin, Rider, or System)
     */
    public function changedBy(): MorphTo
    {
        return $this->morphTo('changed_by');
    }
}
