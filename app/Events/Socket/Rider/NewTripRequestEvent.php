<?php

declare(strict_types=1);

namespace App\Events\Socket\Rider;

use App\Http\Resources\Api\V1\Rider\Trip\TripRequestResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTripRequestEvent implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{trip_request: array, map_locations: array, formatted_locations: array, payment: array}  $tripData
     */
    public function __construct(
        public readonly int $riderId,
        public readonly array $tripData,
    ) {
        $this->afterCommit();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("rider.{$this->riderId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.new_request';
    }

    public function broadcastWith(): array
    {
        return (new TripRequestResource($this->tripData))
            ->resolve();
    }
}
