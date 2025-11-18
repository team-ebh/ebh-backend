<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Trip Declined Event
 *
 * Sent to customer when no riders are available or all decline the trip
 */
class TripDeclinedEvent extends BaseSocketEvent
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly string $reason,
    ) {}

    public function getEventName(): string
    {
        return 'trip.declined';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'reason' => $this->reason,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
