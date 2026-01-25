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
 * Checks if there's an existing pending payment for the order.
 * If found, updates its status to locked to allow processing if customer pays through old link.
 * This prevents multiple pending payments for the same order while allowing locked payments to be processed.
 */
readonly class CheckExistingPendingPaymentPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        // Check for pending payment by order_id
        $existingPayment = $this->paymentRepository->findPendingPaymentForOrder(
            $context->trip->{Trip::COLUMN_ORDER_ID}
        );

        if ($existingPayment) {
            $this->lockExistingPendingPayment($existingPayment);
        }

        return $next($context);
    }

    /**
     * Lock existing pending payment
     *
     * Locks the payment so it can still be processed if customer pays through old link
     */
    private function lockExistingPendingPayment(Payment $payment): void
    {
        $this->paymentRepository->update($payment, [
            Payment::COLUMN_STATUS => PaymentStatusEnum::LOCKED,
        ]);
    }
}
