<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Interfaces\Repositories\Api\V1\Customer\OrderRepositoryInterface;
use App\Models\Trip;
use Closure;

/**
 * Create Scheduled Trip Order Pipe
 *
 * Creates order for scheduled trip without changing trip status.
 * The status remains DRAFT until the scheduled time when ProcessScheduledTripJob runs.
 */
readonly class CreateScheduledTripOrderPipe
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        $trip = $context->dto->trip;

        // Create order with trip price
        $order = $this->orderRepository->createOrder(
            customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
            totalPrice: (float) $trip->{Trip::COLUMN_TOTAL_PRICE},
            currency: $trip->{Trip::COLUMN_CURRENCY},
            paymentMethod: $context->dto->paymentMethod
        );

        // Update trip with order_id only (keep status as DRAFT)
        $trip->update([
            Trip::COLUMN_ORDER_ID => $order->{$order::COLUMN_ID},
        ]);

        $context->order = $order;

        return $next($context);
    }
}
