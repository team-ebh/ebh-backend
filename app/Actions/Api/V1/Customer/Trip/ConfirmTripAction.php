<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Trip\TripNotPendingException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;

/**
 * Confirm Trip Action
 *
 * Confirms the trip and changes status to PENDING_RIDER (searching for rider)
 * Only DRAFT trips can be confirmed
 */
readonly class ConfirmTripAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    /**
     * @throws TripNotPendingException
     * @throws \Throwable
     */
    public function __invoke(ConfirmTripDTO $dto): void
    {
        throw_if(
            ! $dto->trip->isDraft(),
            TripNotPendingException::class
        );

        $this->tripRepository->updateStatus(
            $dto->trip,
            TripStatusEnum::PENDING_RIDER
        );
    }
}
