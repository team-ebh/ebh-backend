<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\DTOs\Api\V1\Customer\Trip\ChangeRideTypeDTO;

/**
 * Change Ride Type Context
 *
 * Context object passed through the ride type change pipeline
 */
class ChangeRideTypeContext
{
    public ?float $distance = null; // Distance from origin to first destination (A→B)

    public ?float $returnDistance = null; // Distance from first destination to second destination (B→C)

    public float $baseFare;

    public ?float $accessibilityCost = null;

    public ?float $roundTripFee = null;

    public ?float $waitingCharge = null;

    public float $totalPrice;

    public ?array $priceBreakdown = null;

    public ?array $priceEstimation = null;

    public ?array $waitingTimeConfig = null;

    public function __construct(
        public readonly ChangeRideTypeDTO $dto,
    ) {}
}
