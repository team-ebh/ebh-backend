<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripPrice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trip Price Aggregate Trait
 *
 * Contains all relationship methods for the TripPrice model
 */
trait TripPriceAggregate
{
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, TripPrice::COLUMN_TRIP_ID);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(TripLocation::class, TripPrice::COLUMN_FROM);
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(TripLocation::class, TripPrice::COLUMN_TO);
    }
}
