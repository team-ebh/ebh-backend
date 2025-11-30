<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Trip\TripRequestStatusEnum;
use App\Traits\Model\Aggregates\TripRequestStatusLogAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class TripRequestStatusLog extends Model
{
    use HasDefaultColumnModelTrait;
    use TripRequestStatusLogAggregate;

    public const string COLUMN_TRIP_REQUEST_ID = 'trip_request_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_CHANGED_BY_TYPE = 'changed_by_type';

    public const string COLUMN_CHANGED_BY_ID = 'changed_by_id';

    protected function casts(): array
    {
        return [
            self::COLUMN_STATUS => TripRequestStatusEnum::class,
        ];
    }
}
