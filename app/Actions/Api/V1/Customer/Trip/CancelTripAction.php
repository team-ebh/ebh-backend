<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\CancelTripDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Trip\TripCannotBeCancelledException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;

/**
 * Cancel Trip Action
 *
 * Cancels the trip and changes status to CANCELLED
 * Only PENDING or CONFIRMED trips can be cancelled
 */
readonly class CancelTripAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
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

        // TODO:: change rider status and send message to rider
        $this->tripRepository->updateStatus(
            $dto->trip,
            TripStatusEnum::CANCELED_BY_CUSTOMER
        );
    }
}
