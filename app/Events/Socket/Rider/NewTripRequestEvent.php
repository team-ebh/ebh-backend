<?php

declare(strict_types=1);

namespace App\Events\Socket\Rider;

use App\Http\Resources\Api\V1\Socket\TripRequestBroadcastResource;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTripRequestEvent implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable;
    use InteractsWithSockets;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $riderId,
        public readonly Trip $trip,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("rider.{$this->riderId}"),
            new Channel("monitor.rider.{$this->riderId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.new_request';
    }

    public function broadcastWith(): array
    {
        return (new TripRequestBroadcastResource($this->trip))
            ->resolve();
    }
}
