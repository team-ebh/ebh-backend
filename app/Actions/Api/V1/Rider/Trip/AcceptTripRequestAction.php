<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\AcceptTripRequestDTO;
use App\Events\Socket\Customer\TripAcceptedEvent;
use App\Exceptions\Rider\RiderNotAvailableException;
use App\Exceptions\Rider\TripNotAvailableException;
use App\Exceptions\Rider\TripRequestNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use App\Services\Trip\TripDataFormatterService;

/**
 * Accept Trip Request Action
 *
 * Handles rider accepting a trip request
 */
readonly class AcceptTripRequestAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripDataFormatterService $tripDataFormatter,
    ) {}

    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(AcceptTripRequestDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'acceptTrip'], $dto);
    }

    /**
     * Accept the trip request
     *
     * @throws \Throwable
     */
    public function acceptTrip(AcceptTripRequestDTO $dto): array
    {
        // Get rider from repository
        $rider = $this->riderTripRepository->getRider($dto->riderId);

        // Validate accept conditions
        $this->validateAcceptConditions($dto->tripRequest, $dto->riderId, $rider);

        // Accept trip with lock (inside transaction)
        $trip = $this->riderTripRepository->acceptTripRequestWithLock($dto->tripRequest, $rider);

        // Broadcast to customer
        broadcast(new TripAcceptedEvent(
            customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
            tripId: $trip->{Trip::COLUMN_ID},
            riderId: $rider->{Rider::COLUMN_ID}
        ));

        // Prepare formatted response data
        return $this->tripDataFormatter->prepareTripData($dto->tripRequest);
    }

    /**
     * Validate conditions for accepting trip request
     *
     * @throws \Throwable
     */
    private function validateAcceptConditions(TripRequest $tripRequest, int $riderId, Rider $rider): void
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

        // Verify trip is in pending rider status
        throw_if(
            ! $tripRequest->trip->isPendingRider(),
            TripNotAvailableException::class
        );
    }
}
