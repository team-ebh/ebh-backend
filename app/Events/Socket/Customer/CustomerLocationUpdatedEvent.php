<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Customer Location Updated Event
 *
 * Sent to rider when customer's location is updated (for pickup tracking)
 */
class CustomerLocationUpdatedEvent extends BaseSocketEvent
{
    public function __construct(
        public readonly int $riderId,
        public readonly int $tripId,
        public readonly int $customerId,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function getEventName(): string
    {
        return 'trip.customer_location_updated';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'customer_id' => $this->customerId,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("rider.{$this->riderId}"),
        ];
    }
}
