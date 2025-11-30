<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\PickUpTripDTO;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripPickedUpEvent;
use App\Exceptions\Rider\InvalidTripActionException;
use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Services\Trip\TripActionService;

/**
 * Pick Up Trip Action
 *
 * Handles rider picking up passenger at trip location
 */
readonly class PickUpTripAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripActionService $tripActionService,
    ) {}

    /**
     * Execute the action
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function __invoke(PickUpTripDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'markAsPickedUp'], $dto);
    }

    /**
     * Mark location as picked up
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function markAsPickedUp(PickUpTripDTO $dto): array
    {
        $trip = $dto->tripRequest->trip;
        $currentLocation = $this->tripActionService->getCurrentLocation($trip);

        $this->validateTripRequestBelongsToRider($dto->tripRequest, $dto->riderId);
        $this->validatePickUpConditions($currentLocation);

        $this->riderTripRepository->updateTripLocationStatus($currentLocation, TripLocationStatusEnum::PICKED_UP);
        $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::ON_TRIP);

        // Broadcast to customer
        broadcast(new TripPickedUpEvent(
            customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
            tripId: $trip->{Trip::COLUMN_ID},
            riderId: $dto->riderId
        ));

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
     * Validate pick up conditions
     *
     * @throws \Throwable
     */
    private function validatePickUpConditions(?TripLocation $currentLocation): void
    {
        throw_if(
            ! $this->tripActionService->validateCanPickUp($currentLocation),
            InvalidTripActionException::class
        );
    }
}
