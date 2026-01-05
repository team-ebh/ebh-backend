<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CompleteTrip;

use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripCompletedEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Company;
use App\Models\Setting;
use App\Models\Trip;
use App\Services\Trip\TripActionService;
use App\Services\TripPricingService;
use Closure;

readonly class FinalizeAndCalculatePipe
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripActionService $tripActionService,
        private TripPricingService $tripPricingService,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $trip = $payload['trip'];

        // Check if all locations are finished
        $allLocationsFinished = $trip->loadMissing('locations')->locations->every(fn ($location) => $location->isFinished());

        if ($allLocationsFinished) {
            // Calculate and update waiting time for ROUND_TRIP_WAIT
            if ($trip->isRoundTripWithWait()) {
                $this->calculateAndUpdateWaitingTime($trip);
            }

            // Calculate and update commission
            $this->calculateAndUpdateCommission($trip);

            // Complete trip and update rider status
            $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::COMPLETED);

            // Broadcast to customer
            broadcast(new TripCompletedEvent(
                customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
                tripId: $trip->{Trip::COLUMN_ID},
                riderId: $trip->{Trip::COLUMN_RIDER_ID},
                hasPendingPayment: ! $trip->isRoundTrip() && $trip->isKnetPayment()
            ));

            $this->riderTripRepository->updateRiderStatusToOnline($trip->{Trip::COLUMN_RIDER_ID});

            $payload['trip_completed'] = true;
            $payload['next_action'] = null;
        } else {
            $payload['trip_completed'] = false;
            $payload['next_action'] = $this->tripActionService->getNextAction($trip->fresh())?->value;
        }

        return $next($payload);
    }

    /**
     * Calculate and update trip commission
     */
    private function calculateAndUpdateCommission(Trip $trip): void
    {
        // Refresh trip to get latest total_price (in case waiting time was added)
        $trip->refresh();

        // Get commission rate from rider's company
        $commissionRate = $this->getCommissionRate($trip);

        // Calculate commission amount
        $totalPrice = (float) $trip->{Trip::COLUMN_TOTAL_PRICE};
        $commissionAmount = round(($totalPrice * $commissionRate) / 100, 3);

        // Update trip with commission
        $this->riderTripRepository->updateTripCommission($trip, $commissionRate, $commissionAmount);
    }

    /**
     * Get commission rate from rider's company or default setting
     */
    private function getCommissionRate(Trip $trip): float
    {
        // Load rider with company
        $rider = $trip->rider()->with('company')->first();

        if (! $rider) {
            return Setting::getDefaultCommissionRate();
        }

        $company = $rider->company;

        if ($company && $company->{Company::COLUMN_COMMISSION_RATE} !== null && $company->{Company::COLUMN_COMMISSION_RATE} > 0) {
            return (float) $company->{Company::COLUMN_COMMISSION_RATE};
        }

        return Setting::getDefaultCommissionRate();
    }

    /**
     * Calculate and update waiting time for ROUND_TRIP_WAIT trips
     */
    private function calculateAndUpdateWaitingTime(Trip $trip): void
    {
        // Load locations with status logs
        $trip->loadMissing(['locations.statusLogs']);

        // Calculate actual waiting time from status logs
        $waitingTimeMinutes = $this->tripPricingService->calculateActualWaitingTime($trip->locations);

        if ($waitingTimeMinutes === null || $waitingTimeMinutes <= 0) {
            return;
        }

        // Calculate waiting charge
        $waitingCharge = $this->tripPricingService->calculateWaitingCharge($waitingTimeMinutes);

        if ($waitingCharge === null || $waitingCharge <= 0) {
            return;
        }

        // Update trip with waiting time and price
        $this->riderTripRepository->updateTripWaitingTimeAndPrice(
            $trip,
            $waitingTimeMinutes,
            $waitingCharge
        );
    }
}
