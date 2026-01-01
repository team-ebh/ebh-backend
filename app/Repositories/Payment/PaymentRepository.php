<?php

declare(strict_types=1);

namespace App\Repositories\Payment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * Find existing pending payment for an order
     */
    public function findPendingPaymentForOrder(int $orderId): ?Payment
    {
        return Payment::query()
            ->where(Payment::COLUMN_ORDER_ID, $orderId)
            ->where(Payment::COLUMN_STATUS, PaymentStatusEnum::PENDING)
            ->where(function ($query) {
                // Either no expiration (null) or not yet expired
                $query->whereNull(Payment::COLUMN_EXPIRES_AT)
                    ->orWhere(Payment::COLUMN_EXPIRES_AT, '>', now());
            })
            ->first();
    }

    /**
     * Check if payment has valid time remaining (more than threshold)
     *
     * @param  int  $thresholdMinutes  Minimum minutes remaining to consider valid
     */
    public function hasValidTimeRemaining(Payment $payment, int $thresholdMinutes): bool
    {
        if (! $payment->{Payment::COLUMN_EXPIRES_AT}) {
            return false;
        }

        $expiresAt = Carbon::parse($payment->{Payment::COLUMN_EXPIRES_AT});
        $minutesRemaining = now()->diffInMinutes($expiresAt, false);

        return $minutesRemaining > $thresholdMinutes;
    }

    /**
     * Create a new payment record
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * Update payment
     */
    public function update(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }

    /**
     * Find payment by ID
     */
    public function findById(int $id): ?Payment
    {
        return Payment::query()->find($id);
    }

    /**
     * Find payment by gateway reference ID
     */
    public function findByGatewayReferenceId(string $gatewayReferenceId): ?Payment
    {
        return Payment::query()->where(Payment::COLUMN_GATEWAY_REFERENCE_ID, $gatewayReferenceId)->first();
    }
}
