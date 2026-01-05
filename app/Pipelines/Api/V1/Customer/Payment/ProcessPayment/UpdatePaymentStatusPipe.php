<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\ProcessPayment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use Closure;

/**
 * Update Payment Status Pipe
 *
 * Updates payment status in the database based on gateway response
 * Handles both PENDING and LOCKED payment statuses
 */
readonly class UpdatePaymentStatusPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(PaymentProcessContext $context, Closure $next): mixed
    {
        if ($context->payment->isPending()) {
            $this->handlePendingPayment($context);
        } elseif ($context->payment->isLocked()) {
            $this->handleLockedPayment($context);
        }

        // Refresh payment to get updated status
        $context->payment->refresh();

        return $next($context);
    }

    /**
     * Handle pending payment status update
     */
    private function handlePendingPayment(PaymentProcessContext $context): void
    {
        $this->paymentRepository->update($context->payment, [
            Payment::COLUMN_STATUS => $context->paymentStatus,
        ]);
    }

    /**
     * Handle locked payment status update
     *
     * If trip has a paid payment:
     *   - Gateway status is PAID → Update to LOCKED_PAID
     *   - Gateway status is FAILED → No change
     * If trip has no paid payment:
     *   - Gateway status is PAID → Update to PAID
     *   - Gateway status is FAILED → No change
     */
    private function handleLockedPayment(PaymentProcessContext $context): void
    {
        // Only process if gateway returned PAID status
        if ($context->paymentStatus !== PaymentStatusEnum::PAID) {
            return;
        }

        $trip = $context->payment->trip;
        $hasPaidPayment = $trip->order?->paidPayment()->exists() ?? false;

        if ($hasPaidPayment) {
            // Trip already has a paid payment, mark this as LOCKED_PAID
            $this->paymentRepository->update($context->payment, [
                Payment::COLUMN_STATUS => PaymentStatusEnum::LOCKED_PAID,
            ]);
        } else {
            // Trip has no paid payment, mark this as PAID
            $this->paymentRepository->update($context->payment, [
                Payment::COLUMN_STATUS => PaymentStatusEnum::PAID,
            ]);
        }
    }
}
