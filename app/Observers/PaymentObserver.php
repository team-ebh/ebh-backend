<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Payment;
use App\Models\PaymentStatusLog;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->logPaymentStatus($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        if ($payment->isDirty(Payment::COLUMN_STATUS)) {
            $this->logPaymentStatus($payment);
        }
    }

    /**
     * Log payment status change
     */
    private function logPaymentStatus(Payment $payment): void
    {
        $user = getAuthenticatedUser();

        PaymentStatusLog::query()
            ->create([
                PaymentStatusLog::COLUMN_PAYMENT_ID => $payment->{Payment::COLUMN_ID},
                PaymentStatusLog::COLUMN_TRIP_ID => $payment->{Payment::COLUMN_TRIP_ID},
                PaymentStatusLog::COLUMN_STATUS => $payment->{Payment::COLUMN_STATUS},
                PaymentStatusLog::COLUMN_CHANGED_BY_TYPE => $user ? get_class($user) : null,
                PaymentStatusLog::COLUMN_CHANGED_BY_ID => $user?->id,
            ]);
    }
}
