<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\PaymentStatusLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payment Aggregate Trait
 *
 * Contains all relationship methods for the Payment model
 */
trait PaymentAggregate
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, Payment::COLUMN_CUSTOMER_ID);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, Payment::COLUMN_ORDER_ID);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, Payment::COLUMN_ORDER_ID);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, PaymentLog::COLUMN_PAYMENT_ID);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PaymentStatusLog::class, PaymentStatusLog::COLUMN_PAYMENT_ID);
    }
}
