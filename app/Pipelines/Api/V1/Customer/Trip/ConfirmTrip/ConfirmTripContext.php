<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\Models\Order;
use App\Models\Trip;

/**
 * Confirm Trip Context
 *
 * Holds data that flows through the confirmation trip pipeline
 */
class ConfirmTripContext
{
    public Order $order;

    public Trip $demandTrip;

    public function __construct(
        public ConfirmTripDTO $dto
    ) {}
}
