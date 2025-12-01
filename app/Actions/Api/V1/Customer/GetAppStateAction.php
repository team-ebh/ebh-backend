<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer;

use App\DTOs\Api\V1\Customer\AppStateDTO;
use App\Enums\Customer\CustomerAppStateEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;

/**
 * Get App State Action
 *
 * Returns the current state of the customer app
 */
readonly class GetAppStateAction
{
    public function __construct(
        private CustomerTripRepositoryInterface $customerTripRepository,
    ) {}

    public function __invoke(AppStateDTO $dto): CustomerAppStateEnum
    {
        if (! $this->customerTripRepository->existsActiveTrip($dto->customerId)) {
            return CustomerAppStateEnum::NO_TRIP;
        }

        return CustomerAppStateEnum::HAS_ACTIVE_TRIP;
    }
}
