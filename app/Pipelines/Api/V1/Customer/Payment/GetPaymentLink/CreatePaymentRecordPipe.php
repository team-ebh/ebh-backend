<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\Trip;
use Closure;
use Random\RandomException;

/**
 * Create Payment Record Pipe
 *
 * Creates a new pending payment record in the database BEFORE generating payment link.
 * Only runs if payment record doesn't already exist (i.e., not reusing existing payment).
 */
readonly class CreatePaymentRecordPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * @throws RandomException
     */
    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        // If payment already exists (reused existing one), skip creating
        if ($context->payment) {
            return $next($context);
        }

        $trip = $context->trip;
        $enableExpiration = config('payment.enable_expiration', false);
        $expirationMinutes = config('payment.link_expiration_minutes', 15);

        // Create new payment record (without link and gateway_reference_id yet)
        $context->payment = $this->paymentRepository->create([
            Payment::COLUMN_PAYMENT_NUMBER => generatePaymentNumber(),
            Payment::COLUMN_CUSTOMER_ID => $trip->{Trip::COLUMN_CUSTOMER_ID},
            Payment::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            Payment::COLUMN_STATUS => PaymentStatusEnum::PENDING,
            Payment::COLUMN_GATEWAY => PaymentGatewayEnum::from(config('payment.default_gateway')),
            Payment::COLUMN_EXPIRES_AT => $enableExpiration ? now()->addMinutes($expirationMinutes) : null,
            Payment::COLUMN_ATTEMPT => 1,
            Payment::COLUMN_AMOUNT => $trip->{Trip::COLUMN_TOTAL_PRICE},
            Payment::COLUMN_CURRENCY => $trip->{Trip::COLUMN_CURRENCY},
        ]);

        return $next($context);
    }
}
