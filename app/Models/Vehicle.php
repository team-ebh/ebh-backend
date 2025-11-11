<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\Aggregates\VehicleAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasDefaultColumnModelTrait;
    use VehicleAggregate;

    public const string COLUMN_RIDER_ID = 'rider_id';

    public const string COLUMN_CAR_TYPE_ID = 'car_type_id';

    public const string COLUMN_CAR_COLOR_ID = 'car_color_id';

    public const string COLUMN_PASSENGER_CAPACITY_ID = 'passenger_capacity_id';

    public const string COLUMN_CAR_MAKE_ID = 'car_make_id';

    public const string COLUMN_CAR_MODEL_ID = 'car_model_id';

    public const string COLUMN_VEHICLE_TYPE_ID = 'vehicle_type_id';

    public const string COLUMN_YEAR = 'year';

    public const string COLUMN_PLATE_NUMBER = 'plate_number';

    public function getAccessibilityFeatureIdsAttribute(): array
    {
        return $this->accessibilityFeatures()
            ->pluck(VehicleAccessibilityFeature::COLUMN_ACCESSIBILITY_REQUIREMENT_ID)
            ->toArray();
    }

    public function syncAccessibilityFeatures(array $featureIds): void
    {
        // Delete existing
        $this->accessibilityFeatures()->delete();

        // Create new
        foreach ($featureIds as $featureId) {
            VehicleAccessibilityFeature::create([
                VehicleAccessibilityFeature::COLUMN_VEHICLE_ID => $this->id,
                VehicleAccessibilityFeature::COLUMN_ACCESSIBILITY_REQUIREMENT_ID => $featureId,
            ]);
        }
    }
}
