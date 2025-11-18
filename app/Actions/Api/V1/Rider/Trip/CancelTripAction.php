<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\CancelTripDTO;
use App\Exceptions\Rider\TripCannotBeCancelledByRiderException;
use App\Exceptions\Rider\TripRequestNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripRequest;

readonly class CancelTripAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * Cancel a trip by the rider
     *
     * @throws \Throwable
     */
    public function __invoke(CancelTripDTO $dto): Trip
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'cancelTrip'], $dto);
    }

    /**
     * Cancel the trip
     *
     * @throws \Throwable
     */
    public function cancelTrip(CancelTripDTO $dto): Trip
    {
        // Validate cancel conditions
        $this->validateCancelConditions($dto->tripRequest, $dto->riderId);

        // Cancel trip with lock (inside transaction)
        $trip = $this->riderTripRepository->cancelTripRequestWithLock($dto->tripRequest);

        return $trip;
    }

    /**
     * Validate conditions for cancelling trip
     *
     * @throws \Throwable
     */
    private function validateCancelConditions(TripRequest $tripRequest, int $riderId): void
    {
        // Verify trip request belongs to this rider
        throw_if(
            ! $tripRequest->belongsToRider($riderId),
            TripRequestNotBelongToRiderException::class
        );

        // Verify trip request is accepted
        throw_if(
            ! $tripRequest->isAccepted(),
            TripCannotBeCancelledByRiderException::class
        );

        // Verify trip status is cancellable by rider
        throw_if(
            ! $tripRequest->trip->canBeCancelledByRider(),
            TripCannotBeCancelledByRiderException::class
        );
    }
}
