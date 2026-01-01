<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\OrderRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use Closure;

/**
 * Update Payment And Status Pipe
 *
 * Creates order with correct total price (sum of main + demand trips if exists),
 * updates trips with order_id, payment method and status
 */
readonly class UpdatePaymentAndStatusPipe
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        // Calculate total order price (main trip + demand trip if exists)
        $orderTotalPrice = $this->calculateOrderTotalPrice($context);

        // Create order
        $order = $this->orderRepository->createOrder(
            customerId: $context->dto->trip->{Trip::COLUMN_CUSTOMER_ID},
            totalPrice: $orderTotalPrice,
            currency: $context->dto->trip->{Trip::COLUMN_CURRENCY},
            paymentMethod: $context->dto->paymentMethod
        );

        // Update main trip status
        $this->tripRepository->updateOrderAndStatus(
            $context->dto->trip,
            $order->{$order::COLUMN_ID},
            TripStatusEnum::PENDING_RIDER
        );

        // Update demand trip with order_id if exists (single query)
        if (isset($context->demandTrip)) {
            $context->demandTrip->update([
                Trip::COLUMN_ORDER_ID => $order->{$order::COLUMN_ID},
            ]);
        }

        return $next($context);
    }

    /**
     * Calculate order total price
     * Sum of main trip + demand trip (if exists)
     */
    private function calculateOrderTotalPrice(ConfirmTripContext $context): float
    {
        $mainTripPrice = (float) $context->dto->trip->{Trip::COLUMN_TOTAL_PRICE};

        // Add demand trip price if it was created
        if (isset($context->demandTrip)) {
            $demandTripPrice = (float) $context->demandTrip->{Trip::COLUMN_TOTAL_PRICE};

            return round($mainTripPrice + $demandTripPrice, 3);
        }

        return $mainTripPrice;
    }
}
