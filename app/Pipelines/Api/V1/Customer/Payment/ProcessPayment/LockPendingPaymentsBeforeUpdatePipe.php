<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\ProcessPayment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use Closure;

/**
 * Lock Pending Payments Before Update Pipe
 *
 * When processing a LOCKED payment that will become PAID,
 * lock any remaining PENDING payments for the same trip first.
 * This prevents multiple PAID payments for the same trip.
 */
readonly class LockPendingPaymentsBeforeUpdatePipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(PaymentProcessContext $context, Closure $next): mixed
    {
        // Only process if current payment is LOCKED and gateway returned PAID
        if ($context->payment->isLocked() && $context->paymentStatus === PaymentStatusEnum::PAID) {
            $this->lockPendingPayments($context);
        }

        return $next($context);
    }

    /**
     * Lock any pending payments for this trip
     */
    private function lockPendingPayments(PaymentProcessContext $context): void
    {
        $pendingPayment = $this->paymentRepository->findPendingPaymentForTrip(
            $context->payment->{Payment::COLUMN_TRIP_ID}
        );

        if ($pendingPayment) {
            $this->paymentRepository->update($pendingPayment, [
                Payment::COLUMN_STATUS => PaymentStatusEnum::LOCKED,
            ]);
        }
    }
}
