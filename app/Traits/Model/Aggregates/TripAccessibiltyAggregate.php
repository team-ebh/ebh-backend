<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Trip;
use App\Models\TripAccessibility;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trip Accessibility Aggregate Trait
 *
 * Contains all relationship methods for the TripAccessibility model
 */
trait TripAccessibiltyAggregate
{
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, TripAccessibility::COLUMN_TRIP_ID);
    }
}
