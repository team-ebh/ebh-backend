<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripLocationStatusLog extends Model
{
    use HasDefaultColumnModelTrait;

    protected $table = 'trip_location_status_logs';

    public const string COLUMN_TRIP_LOCATION_ID = 'trip_location_id';

    public const string COLUMN_STATUS = 'status';

    protected function casts(): array
    {
        return [
            self::COLUMN_STATUS => TripLocationStatusEnum::class,
        ];
    }

    /**
     * Get the trip location that owns the log
     */
    public function tripLocation(): BelongsTo
    {
        return $this->belongsTo(TripLocation::class, self::COLUMN_TRIP_LOCATION_ID);
    }
}
