<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\BaseSocketEvent;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Trip Status Changed Event
 *
 * Sent to customer when trip status changes (e.g., rider arrived, trip started, trip completed)
 */
class TripStatusChangedEvent extends BaseSocketEvent
{
    public function __construct(
        public readonly int $customerId,
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
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }
}
