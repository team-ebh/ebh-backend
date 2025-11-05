<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Update Trip Prices Pipe
 *
 * Updates trip prices in the database
 */
class UpdateTripPricesPipe
{
    public function __construct(
        private readonly TripRepositoryInterface $tripRepository
    ) {}

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Only update if we have calculated prices (distance was available)
        if (isset($context->totalPrice)) {
            $this->tripRepository->updateTripPrices(
                $context->dto->trip,
                $context->accessibilityCost,
                $context->waitingCharge,
                $context->totalPrice
            );
        }

        return $next($context);
    }
}
