<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Trip Next Drop Off Event
 *
 * Sent to customer when dropped off at an intermediate destination (waiting for pickup again)
 * This event is specific to ROUND_TRIP_WAIT trips where customer exits at first destination
 */
class TripNextDropOffEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly int $riderId,
    ) {}

    public function getEventName(): string
    {
        return 'trip.drop_passenger';
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
