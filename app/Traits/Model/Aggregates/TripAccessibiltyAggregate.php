<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trip Aggregate Trait
 *
 * Contains all relationship methods for the Trip model
 */
trait TripAccessibiltyAggregate
{
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, self::COLUMN_TRIP_ID);
    }
}
