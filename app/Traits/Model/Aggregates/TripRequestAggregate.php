<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use App\Models\TripRequestStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trip Request Aggregate Trait
 *
 * Contains all relationship methods for the TripRequest model
 */
trait TripRequestAggregate
{
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, TripRequest::COLUMN_TRIP_ID);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, TripRequest::COLUMN_RIDER_ID);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TripRequestStatusLog::class, TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID);
    }
}
