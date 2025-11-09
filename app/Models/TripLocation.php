<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Trip\TripLocationTypeEnum;
use App\Traits\Model\Aggregates\TripLocationAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripLocation extends Model
{
    use HasDefaultColumnModelTrait;
    use SoftDeletes;
    use TripLocationAggregate;

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_LOCATION_TITLE = 'location_title';

    public const string COLUMN_LOCATION_SUB_TITLE = 'location_sub_title';

    public const string COLUMN_LATITUDE = 'latitude';

    public const string COLUMN_LONGITUDE = 'longitude';

    public const string COLUMN_TYPE = 'type';

    public const string COLUMN_SEQUENCE = 'sequence';

    public const string COLUMN_STATUS = 'status';

    protected function casts(): array
    {
        return [
            self::COLUMN_TYPE => TripLocationTypeEnum::class,
        ];
    }
}
