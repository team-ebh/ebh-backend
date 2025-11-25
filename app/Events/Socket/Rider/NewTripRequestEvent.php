<?php

declare(strict_types=1);

namespace App\Events\Socket\Rider;

use App\Events\Socket\BaseSocketEvent;
use App\Http\Resources\Api\V1\Rider\Trip\TripRequestResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * New Trip Request Event
 *
 * Dispatched when a new trip request is sent to a rider
 */
class NewTripRequestEvent extends BaseSocketEvent implements ShouldDispatchAfterCommit, ShouldQueue
{
    /**
     * @param  array{trip_request: array, map_locations: array, formatted_locations: array, payment: array}  $tripData
     */
    public function __construct(
        public readonly int $riderId,
        public readonly array $tripData,
    ) {}

    public function getEventName(): string
    {
        return 'trip.new_request';
    }

    public function getEventData(): array
    {
        return (new TripRequestResource($this->tripData))
            ->resolve();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("rider.{$this->riderId}"),
        ];
    }
}
