<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Trip;
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

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, Payment::COLUMN_TRIP_ID);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, PaymentLog::COLUMN_PAYMENT_ID);
    }
}
