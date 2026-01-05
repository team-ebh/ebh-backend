<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Order Aggregate Trait
 *
 * Contains all relationship methods for the Order model
 */
trait OrderAggregate
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, Order::COLUMN_CUSTOMER_ID);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, Trip::COLUMN_ORDER_ID);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, Payment::COLUMN_ORDER_ID);
    }

    public function paidPayment(): HasOne
    {
        return $this->hasOne(Payment::class, Payment::COLUMN_ORDER_ID)
            ->withAttributes(Payment::COLUMN_STATUS, PaymentStatusEnum::PAID);
    }

    public function lastPayment(): HasOne
    {
        return $this->hasOne(Payment::class, Payment::COLUMN_ORDER_ID)
            ->latest('id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class, OrderStatusLog::COLUMN_ORDER_ID);
    }
}
