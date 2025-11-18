<?php

declare(strict_types=1);

namespace App\Events\Socket;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Socket Event
 *
 * All socket events should extend this class for consistent structure
 */
abstract class BaseSocketEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Get the event name that will be sent to clients
     */
    abstract public function getEventName(): string;

    /**
     * Get the data that should be sent to clients
     */
    abstract public function getEventData(): array;

    /**
     * Get the channels the event should broadcast on
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    abstract public function broadcastOn(): array;

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return $this->getEventName();
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'socket',
            'event' => $this->getEventName(),
            'data' => $this->getEventData(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
