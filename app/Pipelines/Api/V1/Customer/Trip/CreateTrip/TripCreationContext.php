<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Models\Trip;

/**
 * Trip Creation Context
 *
 * Context object passed through the trip creation pipeline
 */
class TripCreationContext
{
    public ?array $originLocation = null;

    public ?array $destinationLocation = null;

    public ?float $baseFare = null;

    public ?float $accessibilityCost = null;

    public ?float $estimatedPrice = null;

    public ?Trip $trip = null;

    public ?array $priceBreakdown = null;

    public ?array $priceEstimation = null;

    public function __construct(
        public readonly TripStoreDTO $dto,
    ) {}
}
