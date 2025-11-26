<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\GetActiveTripDTO;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Trip;
use App\Services\Trip\TripActionService;
use App\Services\Trip\TripDataFormatterService;

/**
 * Get Active Trip Action
 *
 * Handles retrieving rider's active trip information
 */
readonly class GetActiveTripAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripRequestRepositoryInterface $tripRequestRepository,
        private TripDataFormatterService $tripDataFormatter,
        private TripActionService $tripActionService,
    ) {}

    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(GetActiveTripDTO $dto): ?array
    {
        // Get active trip for rider
        $activeTrip = $this->riderTripRepository->getActiveTrip($dto->riderId);

        if (! $activeTrip) {
            return null;
        }

        // Get accepted trip request for this trip
        $tripRequest = $this->tripRequestRepository->getAcceptedForTrip(
            $activeTrip->{Trip::COLUMN_ID},
            $dto->riderId
        );

        if (! $tripRequest) {
            return null;
        }

        // Prepare base trip data
        $tripData = $this->tripDataFormatter->prepareTripData($tripRequest);

        // Add next action information
        $nextAction = $this->tripActionService->getNextAction($activeTrip->fresh());

        return array_merge($tripData, [
            'next_action' => $nextAction,
            'trip_completed' => $nextAction === null,
            'customer' => $activeTrip->customer,
        ]);
    }
}
