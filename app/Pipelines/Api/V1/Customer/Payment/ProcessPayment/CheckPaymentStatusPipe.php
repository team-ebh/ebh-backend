<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\ProcessPayment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Check Payment Status Pipe
 *
 * Checks payment status from the gateway (UPayments)
 */
readonly class CheckPaymentStatusPipe
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(PaymentProcessContext $context, Closure $next): mixed
    {
        $response = $this->paymentService->verifyPayment(
            $context->payment->{Payment::COLUMN_ID},
            $context->dto->trackId
        );

        if (! $response->success) {
            Log::critical('Payment get status failed', [
                'payment_id' => $context->payment->{Payment::COLUMN_ID},
            ]);
        }

        $context->paymentStatus = PaymentStatusEnum::getPaymentStatus($response->rawResponse['data']['transaction']['result'] ?? null);

        return $next($context);
    }
}
