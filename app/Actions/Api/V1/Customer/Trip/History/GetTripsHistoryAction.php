<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip\History;

use App\DTOs\Api\V1\Customer\Trip\History\TripHistoryDTO;
use App\Enums\Trip\TripHistoryTypeEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Get Trips History Action
 *
 * Retrieves trip history based on type (upcoming or past)
 */
readonly class GetTripsHistoryAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    public function __invoke(TripHistoryDTO $dto): Collection
    {
        return match ($dto->type) {
            TripHistoryTypeEnum::UPCOMING => $this->tripRepository->getUpcomingTrips($dto->customerId),
            TripHistoryTypeEnum::PAST => $this->tripRepository->getPastTrips($dto->customerId),
        };
    }
}
