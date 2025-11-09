<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\Models\Trip;

/**
 * Trip Status Context
 *
 * Context object passed through the trip status pipeline
 */
class TripStatusContext
{
    public bool $found = false;

    public ?int $arrivedTime = null;

    public ?array $rider = null;

    public ?array $vehicle = null;

    public array $locations = [];

    public function __construct(
        public readonly Trip $trip,
    ) {}
}
