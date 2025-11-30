<?php

declare(strict_types=1);

namespace App\Events\Socket\Rider;

use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Trip Request Locked Event
 *
 * Dispatched when a trip request is locked because another rider accepted the trip
 * Notifies riders that their trip request is no longer available
 */
class TripRequestLockedEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    public function __construct(
        public readonly int $riderId,
        public readonly int $tripId,
        public readonly int $tripRequestId,
    ) {}

    public function getEventName(): string
    {
        return 'trip.request_locked';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
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
