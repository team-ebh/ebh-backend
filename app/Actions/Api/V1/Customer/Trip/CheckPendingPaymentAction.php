<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\CheckPendingPaymentDTO;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;

/**
 * Check Pending Payment Action
 *
 * Checks if customer has any unpaid trips
 * Returns true if customer has unpaid trips, false otherwise
 */
readonly class CheckPendingPaymentAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
    ) {}

    /**
     * Check if customer has pending payment
     */
    public function __invoke(CheckPendingPaymentDTO $dto): bool
    {
        $lastTrip = $this->tripRepository->getLastTrip($dto->customerId);

        if (! $lastTrip) {
            return false;
        }

        return ! $lastTrip->hasCompletedAndPaidPayment();
    }
}
