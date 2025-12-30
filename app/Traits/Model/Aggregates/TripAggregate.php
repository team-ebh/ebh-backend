<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripAccessibility;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Models\TripStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, Trip::COLUMN_ORDER_ID);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, Trip::COLUMN_RIDER_ID);
    }

    public function accessibility(): HasMany
    {
        return $this->hasMany(TripAccessibility::class, TripAccessibility::COLUMN_TRIP_ID);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TripLocation::class, TripLocation::COLUMN_TRIP_ID)
            ->orderBy(TripLocation::COLUMN_SEQUENCE);
    }

    public function originLocations(): HasMany
    {
        return $this->hasMany(TripLocation::class, TripLocation::COLUMN_TRIP_ID)
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::ORIGIN)
            ->orderBy(TripLocation::COLUMN_SEQUENCE);
    }

    public function firstOriginLocation(): HasOne
    {
        return $this->hasOne(TripLocation::class, TripLocation::COLUMN_TRIP_ID)
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::ORIGIN)
            ->orderBy(TripLocation::COLUMN_SEQUENCE);
    }

    public function destinationLocations(): HasMany
    {
        return $this->hasMany(TripLocation::class, TripLocation::COLUMN_TRIP_ID)
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)
            ->orderBy(TripLocation::COLUMN_SEQUENCE);
    }

    public function firstDestinationLocation(): HasOne
    {
        return $this->hasOne(TripLocation::class, TripLocation::COLUMN_TRIP_ID)
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)
            ->orderBy(TripLocation::COLUMN_SEQUENCE);
    }

    public function tripRequests(): HasMany
    {
        return $this->hasMany(TripRequest::class, TripRequest::COLUMN_TRIP_ID);
    }

    public function acceptedTripRequest(): HasOne
    {
        return $this->hasOne(TripRequest::class, TripRequest::COLUMN_TRIP_ID)->accepted();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TripStatusLog::class, TripStatusLog::COLUMN_TRIP_ID);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, Payment::COLUMN_TRIP_ID);
    }

    public function paidPayment(): HasOne
    {
        return $this->hasOne(Payment::class, Payment::COLUMN_TRIP_ID)
            ->withAttributes(Payment::COLUMN_STATUS, PaymentStatusEnum::PAID);
    }

    public function lastPayment(): HasOne
    {
        return $this->hasOne(Payment::class, Payment::COLUMN_TRIP_ID)
            ->latest('id');
    }
}
