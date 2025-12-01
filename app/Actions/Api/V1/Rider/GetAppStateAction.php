<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider;

use App\DTOs\Api\V1\Rider\AppStateDTO;
use App\Enums\Rider\RiderAppStateEnum;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;

/**
 * Get App State Action
 *
 * Returns the current state of the rider app
 */
readonly class GetAppStateAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    public function __invoke(AppStateDTO $dto): RiderAppStateEnum
    {
        $rider = $this->riderRepository->find($dto->riderId);

        // Check if rider is offline
        if ($rider->isOffline()) {
            return RiderAppStateEnum::OFFLINE;
        }

        // Check if rider has an active trip
        $hasActiveTrip = $this->riderTripRepository->existsActiveTrip($dto->riderId);

        if (! $hasActiveTrip) {
            return RiderAppStateEnum::ONLINE_IDLE;
        }

        return RiderAppStateEnum::HAS_ACTIVE_TRIP;
    }
}
