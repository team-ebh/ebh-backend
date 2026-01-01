<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Order\OrderStatusEnum;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderStatusLog extends Model
{
    use HasDefaultColumnModelTrait;

    public const string COLUMN_ORDER_ID = 'order_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_CHANGED_BY_TYPE = 'changed_by_type';

    public const string COLUMN_CHANGED_BY_ID = 'changed_by_id';

    protected function casts(): array
    {
        return [
            self::COLUMN_STATUS => OrderStatusEnum::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, self::COLUMN_ORDER_ID);
    }

    public function changedBy(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, self::COLUMN_CHANGED_BY_TYPE, self::COLUMN_CHANGED_BY_ID);
    }
}
