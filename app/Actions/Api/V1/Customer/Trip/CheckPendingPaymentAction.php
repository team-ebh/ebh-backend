<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\CheckPendingPaymentDTO;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;

/**
 * Check Pending Payment Action
 *
 * Checks if customer has any unpaid trips
 * Returns payment status and price information
 */
readonly class CheckPendingPaymentAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
    ) {}

    public function __invoke(CheckPendingPaymentDTO $dto): array
    {
        $lastTrip = $this->tripRepository->getLastTrip($dto->customerId);

        if (! $lastTrip || $lastTrip->hasPaidPayment()) {
            return [
                'has_pending_payment' => false,
                'price' => null,
            ];
        }

        return [
            'has_pending_payment' => true,
            'price' => [
                'price' => (float) $lastTrip->{Trip::COLUMN_TOTAL_PRICE},
                'currency' => $lastTrip->{Trip::COLUMN_CURRENCY},
            ],
        ];
    }
}
