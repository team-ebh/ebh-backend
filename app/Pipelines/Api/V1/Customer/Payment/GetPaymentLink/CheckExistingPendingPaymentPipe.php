<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use Closure;

/**
 * Check Existing Pending Payment Pipe
 *
 * Checks if there's an existing pending payment with valid time remaining.
 * If found and has more than threshold minutes remaining, reuse it.
 * Otherwise, continue to generate a new payment link.
 */
readonly class CheckExistingPendingPaymentPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        $existingPayment = $this->paymentRepository->findPendingPaymentForTrip($context->trip->id);

        if (! $existingPayment) {
            return $next($context);
        }

        if ($this->shouldReusePayment($existingPayment)) {
            return $this->reuseExistingPayment($context, $existingPayment);
        }

        return $next($context);
    }

    /**
     * Check if existing payment should be reused
     */
    private function shouldReusePayment(Payment $payment): bool
    {
        $enableExpiration = config('payment.enable_expiration', false);

        // If expiration disabled and payment has no expiration, reuse it
        if (! $enableExpiration && ! $payment->{Payment::COLUMN_EXPIRES_AT}) {
            return true;
        }

        // If expiration enabled, check if payment has valid time remaining
        if ($enableExpiration) {
            $thresholdMinutes = config('payment.link_reuse_threshold_minutes', 1);

            return $this->paymentRepository->hasValidTimeRemaining($payment, $thresholdMinutes);
        }

        return false;
    }

    /**
     * Reuse existing payment and exit pipeline early
     */
    private function reuseExistingPayment(PaymentLinkContext $context, Payment $payment): PaymentLinkContext
    {
        $this->paymentRepository->update($payment, [
            Payment::COLUMN_ATTEMPT => $payment->{Payment::COLUMN_ATTEMPT} + 1,
        ]);

        $context->payment = $payment;
        $context->paymentLink = $payment->{Payment::COLUMN_LINK};

        return $context;
    }
}
