<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Trip\TripStatusEnum;
use App\Traits\Model\Aggregates\TripStatusLogAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class TripStatusLog extends Model
{
    use HasDefaultColumnModelTrait;
    use TripStatusLogAggregate;

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_CHANGED_BY_TYPE = 'changed_by_type';

    public const string COLUMN_CHANGED_BY_ID = 'changed_by_id';

    protected function casts(): array
    {
        return [
            self::COLUMN_STATUS => TripStatusEnum::class,
        ];
    }
}
