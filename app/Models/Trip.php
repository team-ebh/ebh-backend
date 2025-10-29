<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Traits\Model\Aggregates\TripAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasDefaultColumnModelTrait;
    use SoftDeletes;
    use TripAggregate;

    public const string COLUMN_CUSTOMER_ID = 'customer_id';

    public const string COLUMN_ORIGIN_LOCATION = 'origin_location';

    public const string COLUMN_ORIGIN_SUB_LOCATION = 'origin_sub_location';

    public const string COLUMN_ORIGIN_LATITUDE = 'origin_latitude';

    public const string COLUMN_ORIGIN_LONGITUDE = 'origin_longitude';

    public const string COLUMN_DESTINATION_LOCATION = 'destination_location';

    public const string COLUMN_DESTINATION_SUB_LOCATION = 'destination_sub_location';

    public const string COLUMN_DESTINATION_LATITUDE = 'destination_latitude';

    public const string COLUMN_DESTINATION_LONGITUDE = 'destination_longitude';

    public const string COLUMN_TRIP_TYPE_ID = 'trip_type_id';

    public const string COLUMN_VEHICLE_TYPE_ID = 'vehicle_type_id';

    public const string COLUMN_PASSENGER_COUNT = 'passenger_count';

    public const string COLUMN_BASE_FARE = 'base_fare';

    public const string COLUMN_ESTIMATED_PRICE = 'estimated_price';

    public const string COLUMN_CURRENCY = 'currency';

    public const string COLUMN_STATUS = 'status';

    protected function casts(): array
    {
        return [
            self::COLUMN_TRIP_TYPE_ID => TripTypeEnum::class,
            self::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::class,
            self::COLUMN_CURRENCY => CurrencyEnum::class,
            self::COLUMN_STATUS => TripStatusEnum::class,
        ];
    }

    #[Scope]
    protected function forCustomer($query, int $customerId)
    {
        return $query->where(self::COLUMN_CUSTOMER_ID, $customerId);
    }

    #[Scope]
    protected function byStatus($query, TripStatusEnum $status)
    {
        return $query->where(self::COLUMN_STATUS, $status);
    }
}
