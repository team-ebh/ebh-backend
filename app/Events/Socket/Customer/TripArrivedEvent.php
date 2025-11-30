<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Trip Arrived Event
 *
 * Sent to customer when rider arrives at pickup location
 */
class TripArrivedEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly int $riderId,
    ) {}

    public function getEventName(): string
    {
        return 'trip.arrived';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'rider_id' => $this->riderId,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
