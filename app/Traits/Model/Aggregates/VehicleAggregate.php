<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Rider;
use App\Models\Vehicle;
use App\Models\VehicleAccessibilityFeature;
use App\Models\VehicleSetting;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vehicle Aggregate Trait
 *
 * Contains all relationship methods for the Vehicle model
 */
trait VehicleAggregate
{
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, Vehicle::COLUMN_RIDER_ID);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, Vehicle::COLUMN_CAR_TYPE_ID);
    }

    public function carColor(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, Vehicle::COLUMN_CAR_COLOR_ID);
    }

    public function passengerCapacity(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, Vehicle::COLUMN_PASSENGER_CAPACITY_ID);
    }

    public function carMake(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, Vehicle::COLUMN_CAR_MAKE_ID);
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, Vehicle::COLUMN_CAR_MODEL_ID);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, Vehicle::COLUMN_VEHICLE_TYPE_ID);
    }

    public function accessibilityFeatures(): HasMany
    {
        return $this->hasMany(VehicleAccessibilityFeature::class, VehicleAccessibilityFeature::COLUMN_VEHICLE_ID);
    }
}
