<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\OrderRepositoryInterface;
use App\Models\Order;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Create a new order
     */
    public function createOrder(
        int $customerId,
        float $totalPrice,
        CurrencyEnum $currency,
        PaymentMethodEnum $paymentMethod
    ): Order {
        return Order::query()->create([
            Order::COLUMN_CUSTOMER_ID => $customerId,
            Order::COLUMN_TOTAL_PRICE => $totalPrice,
            Order::COLUMN_CURRENCY => $currency,
            Order::COLUMN_PAYMENT_METHOD => $paymentMethod,
            Order::COLUMN_STATUS => OrderStatusEnum::PENDING,
        ]);
    }

    /**
     * Find order by ID
     */
    public function findOrderById(int $orderId): ?Order
    {
        return Order::query()->find($orderId);
    }

    /**
     * Find order by ID with relationships
     */
    public function findOrderByIdWithRelations(int $orderId): ?Order
    {
        return Order::query()
            ->with(['trips', 'payments'])
            ->find($orderId);
    }

    /**
     * Find active order for customer
     */
    public function findActiveOrderForCustomer(int $customerId): ?Order
    {
        return Order::query()
            ->where(Order::COLUMN_CUSTOMER_ID, $customerId)
            ->where(Order::COLUMN_STATUS, OrderStatusEnum::PENDING)
            ->first();
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(int $orderId, OrderStatusEnum $status): void
    {
        Order::query()
            ->where(Order::COLUMN_ID, $orderId)
            ->update([Order::COLUMN_STATUS => $status]);
    }

    /**
     * Update order total price
     */
    public function updateOrderTotalPrice(int $orderId, float $totalPrice): bool
    {
        return Order::query()
            ->where(Order::COLUMN_ID, $orderId)
            ->update([Order::COLUMN_TOTAL_PRICE => $totalPrice]);
    }

    /**
     * Check if all trips in order are completed
     */
    public function areAllTripsCompleted(int $orderId): bool
    {
        $order = $this->findOrderByIdWithRelations($orderId);

        if (! $order || $order->trips->isEmpty()) {
            return false;
        }

        // Check if all trips are completed
        return $order->trips->every(fn ($trip) => $trip->{$trip::COLUMN_STATUS} === TripStatusEnum::COMPLETED);
    }

    /**
     * Check if any trip in order is cancelled
     */
    public function hasAnyCancelledTrip(int $orderId): bool
    {
        $order = $this->findOrderByIdWithRelations($orderId);

        if (! $order || $order->trips->isEmpty()) {
            return false;
        }

        // Check if any trip is cancelled
        return $order->trips->contains(function ($trip) {
            return in_array($trip->{$trip::COLUMN_STATUS}, [
                TripStatusEnum::CANCELED_BY_CUSTOMER,
                TripStatusEnum::CANCELLED_BY_RIDER,
            ], true);
        });
    }

    /**
     * Calculate total price from all trips in order
     */
    public function calculateOrderTotalPrice(int $orderId): float
    {
        $order = $this->findOrderByIdWithRelations($orderId);

        if (! $order || $order->trips->isEmpty()) {
            return 0.0;
        }

        return $order->trips->sum(fn ($trip) => $trip->{$trip::COLUMN_TOTAL_PRICE});
    }
}
