<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider;

use App\DTOs\Api\V1\Rider\AppStateDTO;
use App\Enums\Rider\RiderAppStateEnum;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Services\Cache\AppStateCache;

/**
 * Get App State Action
 *
 * Returns the current state of the rider app with caching
 */
readonly class GetAppStateAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    public function __invoke(AppStateDTO $dto): RiderAppStateEnum
    {
        return AppStateCache::rider(
            $dto->riderId,
            fn () => $this->calculateRiderAppState($dto->riderId)
        );
    }

    /**
     * Calculate rider app state from database
     */
    private function calculateRiderAppState(int $riderId): RiderAppStateEnum
    {
        $rider = $this->riderRepository->find($riderId);

        // Check if rider is offline
        if ($rider->isOffline()) {
            return RiderAppStateEnum::OFFLINE;
        }

        // Check if rider has an active trip
        $hasActiveTrip = $this->riderTripRepository->existsActiveTrip($riderId);

        if (! $hasActiveTrip) {
            return RiderAppStateEnum::ONLINE_IDLE;
        }

        return RiderAppStateEnum::HAS_ACTIVE_TRIP;
    }
}
