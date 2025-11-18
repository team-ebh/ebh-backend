<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use App\Models\Rider;
use App\Models\Trip;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Trip Accepted Event
 *
 * Sent to customer when a rider accepts their trip request
 */
class TripAcceptedEvent extends BaseSocketEvent
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly int $riderId,
    ) {}

    public function getEventName(): string
    {
        return 'trip.accepted';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
