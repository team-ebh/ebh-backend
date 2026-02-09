<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip\History;

use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use App\Services\TripPricingService;

/**
 * Get Upcoming Trip Details Action
 *
 * Retrieves detailed information for an upcoming (scheduled) trip
 */
readonly class GetUpcomingTripDetailsAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private TripPricingService $pricingService
    ) {}

    /**
     * Execute the action
     *
     * @throws TripNotBelongToCustomerException
     */
    public function __invoke(int $tripId, int $customerId): array
    {
        $trip = $this->tripRepository->getUpcomingTripWithDetails($tripId, $customerId);

        if (! $trip) {
            throw new TripNotBelongToCustomerException;
        }

        return [
            'trip' => $trip,
            'price_breakdown' => $this->pricingService->buildHistoryPriceBreakdown($trip),
            'total_price' => $this->pricingService->buildHistoryTotalPrice($trip),
        ];
    }
}
