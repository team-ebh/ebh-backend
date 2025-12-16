<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Log Aggregate Trait
 *
 * Contains all relationship methods for the PaymentLog model
 */
trait PaymentLogAggregate
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, PaymentLog::COLUMN_CUSTOMER_ID);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, PaymentLog::COLUMN_PAYMENT_ID);
    }
}
