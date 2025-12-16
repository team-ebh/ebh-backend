<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\ProcessPayment;

use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use Closure;

/**
 * Update Payment Status Pipe
 *
 * Updates payment status in the database based on gateway response
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
            $this->paymentRepository->update($context->payment, [
                Payment::COLUMN_STATUS => $context->paymentStatus,
            ]);
        }

        // Refresh payment to get updated status
        $context->payment->refresh();

        return $next($context);
    }
}
