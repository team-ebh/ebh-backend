<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\CompleteTripDTO;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripCompletedEvent;
use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Exceptions\Rider\TripNotInProgressException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Services\Trip\TripActionService;

/**
 * Complete Trip Action
 *
 * Handles rider completing a trip location (origin or destination)
 */
readonly class CompleteTripAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripActionService $tripActionService,
    ) {}

    /**
     * Execute the action
     *
     * @return array{next_action: string|null, trip_completed: bool}
     *
     * @throws \Throwable
     */
    public function __invoke(CompleteTripDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'markAsCompleted'], $dto);
    }

    /**
     * Mark location as completed
     *
     * @return array{next_action: string|null, trip_completed: bool}
     *
     * @throws \Throwable
     */
    public function markAsCompleted(CompleteTripDTO $dto): array
    {
        $trip = $dto->tripRequest->load('trip')->trip;

        // Validate trip is in progress
        $this->validateTripInProgress($trip);

        $currentLocation = $this->tripActionService->getCurrentLocation($trip);

        // Validate current location exists
        $this->validateCurrentLocationExists($currentLocation);

        $lastLocation = $this->tripActionService->getLastLocation($trip);
        $isLastLocation = $this->tripActionService->isSameLocation($currentLocation, $lastLocation);

        $this->validateTripRequestBelongsToRider($dto->tripRequest, $dto->riderId);
        $this->validateCompleteConditions($currentLocation);

        $this->riderTripRepository->updateTripLocationStatus(
            $currentLocation,
            $isLastLocation ? TripLocationStatusEnum::COMPLETED : TripLocationStatusEnum::DROPPED_OFF,
        );
        $tripCompleted = $this->checkAndCompleteTrip($trip);

        $nextAction = $tripCompleted ? null : $this->tripActionService->getNextAction($trip->fresh());

        return [
            'next_action' => $nextAction,
            'trip_completed' => $tripCompleted,
        ];
    }

    /**
     * Validate trip is in progress (not completed)
     *
     * @throws \Throwable
     */
    private function validateTripInProgress(Trip $trip): void
    {
        throw_if(
            $trip->isCompleted(),
            TripNotInProgressException::class
        );
    }

    /**
     * Validate current location exists
     *
     * @throws \Throwable
     */
    private function validateCurrentLocationExists(?TripLocation $currentLocation): void
    {
        throw_if(
            is_null($currentLocation),
            TripNotInProgressException::class
        );
    }

    /**
     * Validate trip request belongs to rider
     *
     * @throws \Throwable
     */
    private function validateTripRequestBelongsToRider(TripRequest $tripRequest, int $riderId): void
    {
        throw_if(
            ! $tripRequest->belongsToRider($riderId),
            TripNotBelongToRiderException::class
        );

        throw_if(
            ! $tripRequest->isAccepted() || $tripRequest->trip->isDraft(),
            InvalidTripActionException::class
        );
    }

    /**
     * Validate complete conditions
     *
     * @throws \Throwable
     */
    private function validateCompleteConditions(?TripLocation $currentLocation): void
    {
        throw_if(
            ! $this->tripActionService->validateCanComplete($currentLocation),
            InvalidTripActionException::class
        );
    }

    /**
     * Check if all locations are finished and complete trip if needed
     */
    private function checkAndCompleteTrip(Trip $trip): bool
    {
        $allLocationsFinished = $trip->locations->every(fn ($location) => $location->isFinished());

        if ($allLocationsFinished) {
            $this->completeTripAndUpdateRider($trip);

            return true;
        }

        return false;
    }

    /**
     * Complete trip and update rider status
     */
    private function completeTripAndUpdateRider(Trip $trip): void
    {
        $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::COMPLETED);

        // Broadcast to customer
        broadcast(new TripCompletedEvent(
            customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
            tripId: $trip->{Trip::COLUMN_ID},
            riderId: $trip->{Trip::COLUMN_RIDER_ID}
        ));

        $this->riderTripRepository->updateRiderStatusToOnline($trip->{Trip::COLUMN_RIDER_ID});
    }
}
