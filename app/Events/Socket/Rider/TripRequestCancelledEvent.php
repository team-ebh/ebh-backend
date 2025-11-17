<?php

declare(strict_types=1);

namespace App\Events\Socket\Rider;

use App\Http\Resources\Api\V1\Socket\TripRequestCancelledBroadcastResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripRequestCancelledEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $riderId,
        public readonly int $tripId,
        public readonly string $reason,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("rider.{$this->riderId}"),
            new \Illuminate\Broadcasting\Channel("monitor.rider.{$this->riderId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.request_cancelled';
    }

    public function broadcastWith(): array
    {
        return (new TripRequestCancelledBroadcastResource(
            tripId: $this->tripId,
            reason: $this->reason
        ))->resolve();
    }
}
