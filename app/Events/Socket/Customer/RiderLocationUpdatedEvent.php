<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Rider Location Updated Event
 *
 * Sent to customer when rider updates their location during an active trip
 */
class RiderLocationUpdatedEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly int $riderId,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function getEventName(): string
    {
        return 'trip.rider_location_updated';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'rider_id' => $this->riderId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
