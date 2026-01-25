<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Trip Searching For Rider Event
 *
 * Sent to customer when a scheduled trip starts searching for riders
 * This notifies the customer that their scheduled trip is now active and looking for available riders
 */
class TripSearchingForRiderEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
    ) {}

    public function getEventName(): string
    {
        return 'trip.searching_for_rider';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'customer_id' => $this->customerId,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
