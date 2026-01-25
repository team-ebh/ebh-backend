<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\OrderRepositoryInterface;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\Trip;
use Closure;
use Random\RandomException;

/**
 * Create Payment Record Pipe
 *
 * Creates a new pending payment record in the database BEFORE generating payment link.
 * For orders with multiple trips, payment amount is sum of all trips.
 * Only runs if payment record doesn't already exist (i.e., not reusing existing payment).
 */
readonly class CreatePaymentRecordPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private OrderRepositoryInterface $orderRepository
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

        // Calculate payment amount from order's total price (sum of all trips in order)
        $paymentAmount = $this->orderRepository->calculateOrderTotalPrice($trip->{Trip::COLUMN_ORDER_ID});

        // Create new payment record (without link and gateway_reference_id yet)
        // Payment is associated with Order to handle both single and multi-trip orders
        $context->payment = $this->paymentRepository->create([
            Payment::COLUMN_PAYMENT_NUMBER => generatePaymentNumber(),
            Payment::COLUMN_CUSTOMER_ID => $trip->{Trip::COLUMN_CUSTOMER_ID},
            Payment::COLUMN_ORDER_ID => $trip->{Trip::COLUMN_ORDER_ID},
            Payment::COLUMN_STATUS => PaymentStatusEnum::PENDING,
            Payment::COLUMN_GATEWAY => PaymentGatewayEnum::from(config('payment.default_gateway')),
            Payment::COLUMN_EXPIRES_AT => $enableExpiration ? now()->addMinutes($expirationMinutes) : null,
            Payment::COLUMN_ATTEMPT => 1,
            Payment::COLUMN_AMOUNT => $paymentAmount,
            Payment::COLUMN_CURRENCY => $trip->{Trip::COLUMN_CURRENCY},
        ]);

        return $next($context);
    }
}
