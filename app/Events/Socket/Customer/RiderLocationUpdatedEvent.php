<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Rider Location Updated Event
 *
 * Sent to customer when rider's location is updated during an active trip
 */
class RiderLocationUpdatedEvent extends BaseSocketEvent
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?float $heading = null,
        public readonly ?float $speed = null,
    ) {}

    public function getEventName(): string
    {
        return 'trip.rider_location_updated';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'heading' => $this->heading,
                'speed' => $this->speed,
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
