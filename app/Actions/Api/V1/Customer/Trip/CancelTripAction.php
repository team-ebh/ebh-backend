<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\CancelTripDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Trip\TripCannotBeCancelledException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Jobs\CancelTripRequestsJob;
use App\Models\Trip;

/**
 * Cancel Trip Action
 *
 * Cancels the trip and changes status to CANCELLED
 * Only PENDING or CONFIRMED trips can be cancelled
 * Dispatches background job to cancel all trip requests and notify riders
 */
readonly class CancelTripAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * @throws TripCannotBeCancelledException
     * @throws \Throwable
     */
    public function __invoke(CancelTripDTO $dto): void
    {
        throw_if(
            ! $dto->trip->canCancelTrip(),
            TripCannotBeCancelledException::class
        );

        safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($dto) {
                $this->tripRepository->updateStatus(
                    $dto->trip,
                    TripStatusEnum::CANCELED_BY_CUSTOMER
                );

                if ($dto->trip->{Trip::COLUMN_RIDER_ID}) {
                    $this->riderTripRepository->updateRiderStatusToOnline($dto->trip->{Trip::COLUMN_RIDER_ID});
                }
            });

        // Dispatch background job to cancel all trip requests and notify riders
        CancelTripRequestsJob::dispatch(
            tripId: $dto->trip->{Trip::COLUMN_ID},
            customerId: $dto->trip->{Trip::COLUMN_CUSTOMER_ID},
            riderId: $dto->trip->{Trip::COLUMN_RIDER_ID}
        );
    }
}
