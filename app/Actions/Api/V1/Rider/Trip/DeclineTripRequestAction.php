<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\DeclineTripRequestDTO;
use App\Exceptions\Rider\RiderNotAvailableException;
use App\Exceptions\Rider\TripNotAvailableException;
use App\Exceptions\Rider\TripRequestNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Rider;
use App\Models\TripRequest;

/**
 * Decline Trip Request Action
 *
 * Handles rider declining a trip request
 */
readonly class DeclineTripRequestAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(DeclineTripRequestDTO $dto): array
    {
        return safeProcess()
            ->do([$this, 'declineTrip'], $dto);
    }

    /**
     * Decline the trip request
     *
     * @throws \Throwable
     */
    public function declineTrip(DeclineTripRequestDTO $dto): array
    {
        // Get rider from repository
        $rider = $this->riderTripRepository->getRider($dto->riderId);

        // Validate decline conditions
        $this->validateDeclineConditions($dto->tripRequest, $dto->riderId, $rider);

        // Decline trip request (update status to DECLINED)
        $this->riderTripRepository->declineTripRequest($dto->tripRequest);

        return [
            'trip_request_id' => $dto->tripRequest->{TripRequest::COLUMN_ID},
            'message' => trans('trips.trip_request_declined_successfully'),
        ];
    }

    /**
     * Validate conditions for declining trip request
     *
     * @throws \Throwable
     */
    private function validateDeclineConditions(TripRequest $tripRequest, int $riderId, Rider $rider): void
    {
        // Verify rider is online and available
        throw_if(
            ! $rider->isOnline(),
            RiderNotAvailableException::class
        );

        // Verify trip request belongs to this rider
        throw_if(
            ! $tripRequest->belongsToRider($riderId),
            TripRequestNotBelongToRiderException::class
        );

        // Verify trip request is in pending status
        throw_if(
            ! $tripRequest->isPending(),
            TripNotAvailableException::class
        );
    }
}
