<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderStatusLog;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     * Log the initial status when order is first created.
     */
    public function created(Order $order): void
    {
        $this->logStatusChange($order, $order->{Order::COLUMN_STATUS});
    }

    /**
     * Handle the Order "updated" event.
     * Log status changes when order is updated.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty(Order::COLUMN_STATUS)) {
            $this->logStatusChange($order, $order->{Order::COLUMN_STATUS});
        }
    }

    /**
     * Create a status log entry for the order.
     */
    private function logStatusChange(Order $order, mixed $status): void
    {
        $changedBy = getAuthenticatedUser();

        OrderStatusLog::query()->create([
            OrderStatusLog::COLUMN_ORDER_ID => $order->{Order::COLUMN_ID},
            OrderStatusLog::COLUMN_STATUS => $status,
            OrderStatusLog::COLUMN_CHANGED_BY_TYPE => $changedBy?->getMorphClass(),
            OrderStatusLog::COLUMN_CHANGED_BY_ID => $changedBy?->id,
        ]);
    }
}
