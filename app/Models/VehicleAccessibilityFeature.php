<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAccessibilityFeature extends Model
{
    use HasDefaultColumnModelTrait;

    protected $table = 'vehicle_accessibility_features';

    public const string COLUMN_VEHICLE_ID = 'vehicle_id';

    public const string COLUMN_ACCESSIBILITY_REQUIREMENT_ID = 'accessibility_requirement_id';

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, self::COLUMN_VEHICLE_ID);
    }
}
