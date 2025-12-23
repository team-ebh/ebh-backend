<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Enums\Payment\PaymentStatusEnum;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\Trip;
use Closure;

/**
 * Check Existing Pending Payment Pipe
 *
 * Checks if there's an existing pending payment for the trip.
 * If found, updates its status to expired to ensure a fresh payment is created.
 * This prevents multiple pending payments for the same trip.
 */
readonly class CheckExistingPendingPaymentPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        $existingPayment = $this->paymentRepository->findPendingPaymentForTrip($context->trip->{Trip::COLUMN_ID});

        if ($existingPayment) {
            $this->lockedExistingPendingPayment($existingPayment);
        }

        return $next($context);
    }

    /**
     * Expire existing pending payment
     */
    private function lockedExistingPendingPayment(Payment $payment): void
    {
        $this->paymentRepository->update($payment, [
            Payment::COLUMN_STATUS => PaymentStatusEnum::LOCKED,
        ]);
    }
}
