<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Trip Next Pick Up Event
 *
 * Sent to customer when picked up again at an intermediate destination after waiting
 * This event is specific to ROUND_TRIP_WAIT trips where customer re-enters vehicle
 */
class TripNextPickUpEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly int $riderId,
        public readonly int $locationId,
        public readonly int $locationSequence,
    ) {}

    public function getEventName(): string
    {
        return 'trip.next_pick_up';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'rider_id' => $this->riderId,
            'location_id' => $this->locationId,
            'location_sequence' => $this->locationSequence,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
