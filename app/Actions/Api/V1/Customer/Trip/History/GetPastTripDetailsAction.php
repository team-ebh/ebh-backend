<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip\History;

use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use App\Services\TripPricingService;

/**
 * Get Past Trip Details Action
 *
 * Retrieves detailed information for a past (completed/canceled) trip
 */
readonly class GetPastTripDetailsAction
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
        $trip = $this->tripRepository->getPastTripWithDetails($tripId, $customerId);

        if (! $trip) {
            throw new TripNotBelongToCustomerException;
        }

        return [
            'trip' => $trip,
            'price_breakdown' => $this->buildPriceBreakdown($trip),
        ];
    }

    /**
     * Build price breakdown from trip data
     */
    private function buildPriceBreakdown(Trip $trip): array
    {
        $pricing = [
            'base_fare' => $trip->{Trip::COLUMN_BASE_FARE},
            'accessibility_cost' => $trip->{Trip::COLUMN_ACCESSIBILITY_PRICE},
            'round_trip_fee' => $trip->{Trip::COLUMN_ROUND_TRIP_PRICE},
            'waiting_charge' => $trip->{Trip::COLUMN_WAITING_PRICE},
            'total_price' => $trip->{Trip::COLUMN_TOTAL_PRICE},
        ];

        return $this->pricingService->buildPriceBreakdownFromTrip(
            $trip->{Trip::COLUMN_RIDE_TYPE},
            $pricing,
            $trip->accessibility,
            $trip->{Trip::COLUMN_WAITING_TIME}
        );
    }
}
