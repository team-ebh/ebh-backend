<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Payment;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    /**
     * Find existing pending payment for a trip
     */
    public function findPendingPaymentForTrip(int $tripId): ?Payment;

    /**
     * Check if payment has valid time remaining (more than threshold)
     *
     * @param  int  $thresholdMinutes  Minimum minutes remaining to consider valid
     */
    public function hasValidTimeRemaining(Payment $payment, int $thresholdMinutes): bool;

    /**
     * Create a new payment record
     */
    public function create(array $data): Payment;

    /**
     * Update payment
     */
    public function update(Payment $payment, array $data): bool;

    /**
     * Find payment by ID
     */
    public function findById(int $id): ?Payment;

    /**
     * Find payment by gateway reference ID
     */
    public function findByGatewayReferenceId(string $gatewayReferenceId): ?Payment;
}
