<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Traits\Model\Aggregates\TripAccessibiltyAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class TripAccessibility extends Model
{
    use HasDefaultColumnModelTrait;
    use TripAccessibiltyAggregate;

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_ACCESSIBILITY_REQUIREMENT = 'accessibility_requirement';

    protected $table = 'trip_accessibility';

    protected function casts(): array
    {
        return [
            self::COLUMN_ACCESSIBILITY_REQUIREMENT => AccessibilityRequirementsEnum::class,
        ];
    }
}
