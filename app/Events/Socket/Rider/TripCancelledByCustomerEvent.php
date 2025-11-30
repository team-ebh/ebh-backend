<?php

declare(strict_types=1);

namespace App\Events\Socket\Rider;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Trip Cancelled By Customer Event
 *
 * Sent to rider when a customer cancels their trip
 */
class TripCancelledByCustomerEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    public function __construct(
        public readonly int $riderId,
        public readonly int $tripId,
        public readonly int $customerId,
        public readonly ?int $tripRequestId = null,
    ) {}

    public function getEventName(): string
    {
        return 'trip.cancelled_by_customer';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'customer_id' => $this->customerId,
            'trip_request_id' => $this->tripRequestId,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("rider.{$this->riderId}"),
        ];
    }
}
