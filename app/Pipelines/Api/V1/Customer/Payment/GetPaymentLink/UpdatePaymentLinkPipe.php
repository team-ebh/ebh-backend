<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use Closure;

/**
 * Update Payment Link Pipe
 *
 * Updates the payment record with the generated link and gateway reference ID.
 * Runs AFTER GeneratePaymentLinkPipe has fetched the link from the gateway.
 */
readonly class UpdatePaymentLinkPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        $payment = $context->payment;

        // Update payment with link and gateway reference ID
        $this->paymentRepository->update($payment, [
            Payment::COLUMN_LINK => $context->paymentLink,
            Payment::COLUMN_GATEWAY_REFERENCE_ID => $context->gatewayReferenceId,
        ]);

        return $next($context);
    }
}
