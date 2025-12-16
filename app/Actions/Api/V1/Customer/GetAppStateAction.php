<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer;

use App\DTOs\Api\V1\Customer\AppStateDTO;
use App\Enums\Customer\CustomerAppStateEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;

/**
 * Get App State Action
 *
 * Returns the current state of the customer app
 */
readonly class GetAppStateAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private CustomerTripRepositoryInterface $customerTripRepository,
    ) {}

    public function __invoke(AppStateDTO $dto): CustomerAppStateEnum
    {
        // Check if customer has active trip (first priority)
        if ($this->customerTripRepository->existsActiveTrip($dto->customerId)) {
            return CustomerAppStateEnum::HAS_ACTIVE_TRIP;
        }

        // Check if customer has completed trips without payment
        $lastTrip = $this->tripRepository->getLastTrip($dto->customerId);

        if ($lastTrip && ! $lastTrip->hasPaidPayment()) {
            return CustomerAppStateEnum::HAS_PENDING_PAYMENT;
        }

        return CustomerAppStateEnum::NO_TRIP;
    }
}
