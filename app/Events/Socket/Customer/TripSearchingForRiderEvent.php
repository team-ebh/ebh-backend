<?php

declare(strict_types=1);

namespace App\Events\Socket\Customer;

use App\Http\Resources\Api\V1\Socket\TripSearchingBroadcastResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripSearchingForRiderEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $customerId,
        public readonly int $tripId,
        public readonly int $riderCount,
        public readonly int $radiusMeters,
        public readonly int $searchAttempt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
            new \Illuminate\Broadcasting\Channel("monitor.customer.{$this->customerId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.searching_for_rider';
    }

    public function broadcastWith(): array
    {
        return (new TripSearchingBroadcastResource(
            tripId: $this->tripId,
            riderCount: $this->riderCount,
            radiusMeters: $this->radiusMeters,
            searchAttempt: $this->searchAttempt
        ))->resolve();
    }
}
