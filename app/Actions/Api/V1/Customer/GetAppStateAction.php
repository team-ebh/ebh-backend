<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer;

use App\DTOs\Api\V1\Customer\AppStateDTO;
use App\Enums\Customer\CustomerAppStateEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Services\Cache\AppStateCache;

/**
 * Get App State Action
 *
 * Returns the current state of the customer app with caching
 */
readonly class GetAppStateAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private CustomerTripRepositoryInterface $customerTripRepository,
    ) {}

    public function __invoke(AppStateDTO $dto): CustomerAppStateEnum
    {
        return AppStateCache::customer(
            $dto->customerId,
            fn () => $this->calculateCustomerAppState($dto->customerId)
        );
    }

    /**
     * Calculate customer app state from database
     */
    private function calculateCustomerAppState(int $customerId): CustomerAppStateEnum
    {
        // Check if customer has active trip (first priority)
        if ($this->customerTripRepository->existsActiveTrip($customerId)) {
            return CustomerAppStateEnum::HAS_ACTIVE_TRIP;
        }

        // Check if customer has scheduled trip (from ROUND_TRIP)
        if ($this->customerTripRepository->existsScheduledTrip($customerId)) {
            return CustomerAppStateEnum::HAS_SCHEDULED_TRIP;
        }

        // Check if customer has completed trips without payment
        $lastTrip = $this->tripRepository->getLastTrip($customerId);

        if ($lastTrip && ! $lastTrip->hasPaidPayment() && ! $lastTrip->isRoundTrip()) {
            return CustomerAppStateEnum::HAS_PENDING_PAYMENT;
        }

        return CustomerAppStateEnum::NO_TRIP;
    }
}
