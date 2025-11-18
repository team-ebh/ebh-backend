<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Customer;
use App\Models\Trip;
use App\Models\TripAccessibility;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Models\TripStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trip Aggregate Trait
 *
 * Contains all relationship methods for the Trip model
 */
trait TripAggregate
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, Trip::COLUMN_CUSTOMER_ID);
    }

    public function accessibility(): HasMany
    {
        return $this->hasMany(TripAccessibility::class, TripAccessibility::COLUMN_TRIP_ID);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TripLocation::class, TripLocation::COLUMN_TRIP_ID);
    }

    public function tripRequests(): HasMany
    {
        return $this->hasMany(TripRequest::class, TripRequest::COLUMN_TRIP_ID);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TripStatusLog::class, TripStatusLog::COLUMN_TRIP_ID);
    }
}
