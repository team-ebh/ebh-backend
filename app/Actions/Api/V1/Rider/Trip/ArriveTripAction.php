<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\ArrivedTripDTO;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Services\Trip\TripActionService;

/**
 * Arrived Trip Action
 *
 * Handles rider arriving at a trip location
 */
readonly class ArriveTripAction
{
    public function __construct(
        protected RiderTripRepositoryInterface $riderTripRepository,
        protected TripActionService $tripActionService,
    ) {}

    /**
     * Execute the action
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function __invoke(ArrivedTripDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'markAsArrived'], $dto);
    }

    /**
     * Mark location as arrived
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function markAsArrived(ArrivedTripDTO $dto): array
    {
        $trip = $dto->tripRequest->trip;
        $currentLocation = $this->tripActionService->getCurrentLocation($trip);

        $this->validateTripRequestBelongsToRider($dto->tripRequest, $dto->riderId);
        $this->validateArrivalConditions($currentLocation);

        $this->riderTripRepository->updateTripLocationStatus($currentLocation, TripLocationStatusEnum::ARRIVED);
        $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::ARRIVED);

        $nextAction = $this->tripActionService->getNextAction($trip->fresh());

        return [
            'next_action' => $nextAction,
        ];
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
            ! $tripRequest->isAccepted(),
            InvalidTripActionException::class
        );
    }

    /**
     * Validate arrival conditions
     *
     * @throws \Throwable
     */
    private function validateArrivalConditions(?TripLocation $currentLocation): void
    {
        throw_if(
            ! $this->tripActionService->validateCanArrive($currentLocation),
            InvalidTripActionException::class
        );
    }
}
