<?php

declare(strict_types=1);

namespace App\Events\Socket\Rider;

use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Trip Status Changed Event (Rider)
 *
 * Sent to rider when trip status changes (e.g., customer cancelled, trip completed)
 */
class TripStatusChangedEvent extends BaseSocketEvent
{
    public function __construct(
        public readonly int $riderId,
        public readonly int $tripId,
        public readonly TripStatusEnum $status,
        public readonly ?string $message = null,
    ) {}

    public function getEventName(): string
    {
        return 'trip.status_changed';
    }

    public function getEventData(): array
    {
        return [
            'trip_id' => $this->tripId,
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("rider.{$this->riderId}"),
        ];
    }
}
