<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip\History;

use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Services\TripPricingService;

/**
 * Get Past Trip Details Action
 *
 * Retrieves detailed information for a past (completed/canceled) trip
 */
readonly class GetPastTripDetailsAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripPricingService $pricingService
    ) {}

    /**
     * Execute the action
     *
     * @throws TripNotBelongToRiderException
     */
    public function __invoke(int $tripId, int $riderId): array
    {
        $trip = $this->riderTripRepository->getPastTripWithDetails($tripId, $riderId);

        if (! $trip) {
            throw new TripNotBelongToRiderException;
        }

        return [
            'trip' => $trip,
            'price_breakdown' => $this->pricingService->buildHistoryPriceBreakdown($trip),
        ];
    }
}
