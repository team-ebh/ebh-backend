<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Traits\Model\Aggregates\OrderAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasDefaultColumnModelTrait;
    use OrderAggregate;
    use SoftDeletes;

    public const string COLUMN_CUSTOMER_ID = 'customer_id';

    public const string COLUMN_TOTAL_PRICE = 'total_price';

    public const string COLUMN_CURRENCY = 'currency';

    public const string COLUMN_PAYMENT_METHOD = 'payment_method';

    public const string COLUMN_STATUS = 'status';

    protected function casts(): array
    {
        return [
            self::COLUMN_CURRENCY => CurrencyEnum::class,
            self::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::class,
            self::COLUMN_STATUS => OrderStatusEnum::class,
        ];
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->{self::COLUMN_STATUS} === OrderStatusEnum::PENDING;
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->{self::COLUMN_STATUS} === OrderStatusEnum::COMPLETED;
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->{self::COLUMN_STATUS} === OrderStatusEnum::CANCELLED;
    }

    /**
     * Check if order belongs to customer
     */
    public function belongsToCustomer(int $customerId): bool
    {
        return $this->{self::COLUMN_CUSTOMER_ID} === $customerId;
    }
}
