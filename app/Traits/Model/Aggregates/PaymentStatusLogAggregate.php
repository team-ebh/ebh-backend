<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * PaymentStatusLog Aggregate Trait
 *
 * Contains all relationship methods for the PaymentStatusLog model
 */
trait PaymentStatusLogAggregate
{
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, PaymentStatusLog::COLUMN_PAYMENT_ID);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, PaymentStatusLog::COLUMN_TRIP_ID);
    }

    public function changedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
