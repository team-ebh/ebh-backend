<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Order;

interface OrderRepositoryInterface
{
    /**
     * Create a new order
     */
    public function createOrder(
        int $customerId,
        float $totalPrice,
        CurrencyEnum $currency,
        PaymentMethodEnum $paymentMethod
    ): Order;

    /**
     * Find order by ID
     */
    public function findOrderById(int $orderId): ?Order;

    /**
     * Find order by ID with relationships
     */
    public function findOrderByIdWithRelations(int $orderId): ?Order;

    /**
     * Find active order for customer
     */
    public function findActiveOrderForCustomer(int $customerId): ?Order;

    /**
     * Update order status
     */
    public function updateOrderStatus(int $orderId, OrderStatusEnum $status): bool;

    /**
     * Update order total price
     */
    public function updateOrderTotalPrice(int $orderId, float $totalPrice): bool;

    /**
     * Check if all trips in order are completed
     */
    public function areAllTripsCompleted(int $orderId): bool;

    /**
     * Check if any trip in order is cancelled
     */
    public function hasAnyCancelledTrip(int $orderId): bool;

    /**
     * Calculate total price from all trips in order
     */
    public function calculateOrderTotalPrice(int $orderId): float;
}
