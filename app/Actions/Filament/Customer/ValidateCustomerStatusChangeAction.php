<?php

declare(strict_types=1);

namespace App\Actions\Filament\Customer;

use App\Enums\Customer\CustomerStatusEnum;
use App\Exceptions\Customer\CustomerHasActiveTripException;
use App\Exceptions\Customer\CustomerHasPendingPaymentException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;

/**
 * Validate Customer Status Change Action
 *
 * Checks if a customer can be deactivated (INACTIVE or SUSPENDED)
 * Throws exception if customer has active trip or pending payment
 */
readonly class ValidateCustomerStatusChangeAction
{
    public function __construct(
        private CustomerTripRepositoryInterface $customerTripRepository,
        private TripRepositoryInterface $tripRepository,
    ) {}

    /**
     * @throws CustomerHasActiveTripException
     * @throws CustomerHasPendingPaymentException
     */
    public function __invoke(int $customerId, CustomerStatusEnum $currentStatus, CustomerStatusEnum $newStatus): void
    {
        // Check if changing from ACTIVE to INACTIVE or SUSPENDED
        $isDeactivating = $currentStatus === CustomerStatusEnum::ACTIVE &&
            in_array($newStatus, [CustomerStatusEnum::INACTIVE, CustomerStatusEnum::SUSPENDED]);

        if (! $isDeactivating) {
            return;
        }

        // Check for active trip first
        if ($this->customerTripRepository->existsActiveTrip($customerId)) {
            throw new CustomerHasActiveTripException();
        }

        // Check for pending payment
        $lastTrip = $this->tripRepository->getLastTrip($customerId);
        if ($lastTrip && ! $lastTrip->hasPaidPayment()) {
            throw new CustomerHasPendingPaymentException();
        }
    }
}
