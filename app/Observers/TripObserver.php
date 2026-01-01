<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\Order\OrderStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\OrderRepositoryInterface;
use App\Models\Trip;
use App\Models\TripStatusLog;

class TripObserver
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Handle the Trip "created" event.
     * Log the initial status when trip is first created.
     */
    public function created(Trip $trip): void
    {
        $this->logStatusChange($trip, $trip->{Trip::COLUMN_STATUS});
    }

    /**
     * Handle the Trip "updated" event.
     * Log status changes and update order status when trip is updated.
     */
    public function updated(Trip $trip): void
    {
        if ($trip->isDirty(Trip::COLUMN_STATUS)) {
            $this->logStatusChange($trip, $trip->{Trip::COLUMN_STATUS});
            $this->updateOrderStatus($trip);
        }
    }

    /**
     * Create a status log entry for the trip.
     */
    private function logStatusChange(Trip $trip, mixed $status): void
    {
        $changedBy = getAuthenticatedUser();

        TripStatusLog::query()->create([
            TripStatusLog::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripStatusLog::COLUMN_STATUS => $status,
            TripStatusLog::COLUMN_CHANGED_BY_TYPE => $changedBy?->getMorphClass(),
            TripStatusLog::COLUMN_CHANGED_BY_ID => $changedBy?->id,
        ]);
    }

    /**
     * Update order status based on trip status changes
     *
     * - If any trip is cancelled -> Order status = CANCELLED
     * - If all trips are completed -> Order status = COMPLETED
     * - For CASH payment: mark order as COMPLETED immediately when all trips done
     */
    private function updateOrderStatus(Trip $trip): void
    {
        // If trip doesn't have an order, skip
        if (! $trip->{Trip::COLUMN_ORDER_ID}) {
            return;
        }

        $orderId = $trip->{Trip::COLUMN_ORDER_ID};
        $order = $trip->order;

        // If trip is cancelled, cancel the order
        if ($trip->isCanceledByCustomer() || $trip->isCancelledByRider()) {
            $this->orderRepository->updateOrderStatus($orderId, OrderStatusEnum::CANCELLED);

            return;
        }

        // If trip is completed, check if all trips in order are completed
        if ($trip->isCompleted() && $this->orderRepository->areAllTripsCompleted($orderId)) {
            // For CASH payment, mark order as COMPLETED immediately
            // For KNET, order will be completed after successful payment
            $this->orderRepository->updateOrderStatus($orderId, OrderStatusEnum::COMPLETED);
        }
    }
}
