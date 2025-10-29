<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency\CurrencyEnum;
use App\Traits\Model\Aggregates\TripPriceAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripPrice extends Model
{
    use HasDefaultColumnModelTrait;
    use SoftDeletes;
    use TripPriceAggregate;

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_FROM = 'from';

    public const string COLUMN_TO = 'to';

    public const string COLUMN_BASE_FARE_PRICE = 'base_fare_price';

    public const string COLUMN_CURRENCY = 'currency';

    protected function casts(): array
    {
        return [
            self::COLUMN_CURRENCY => CurrencyEnum::class,
        ];
    }
}
