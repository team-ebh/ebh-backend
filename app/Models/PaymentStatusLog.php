<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Payment\PaymentStatusEnum;
use App\Traits\Model\Aggregates\PaymentStatusLogAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class PaymentStatusLog extends Model
{
    use HasDefaultColumnModelTrait;
    use PaymentStatusLogAggregate;

    public const string COLUMN_PAYMENT_ID = 'payment_id';

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_CHANGED_BY_ID = 'changed_by_id';

    public const string COLUMN_CHANGED_BY_TYPE = 'changed_by_type';

    protected function casts(): array
    {
        return [
            self::COLUMN_STATUS => PaymentStatusEnum::class,
        ];
    }
}
