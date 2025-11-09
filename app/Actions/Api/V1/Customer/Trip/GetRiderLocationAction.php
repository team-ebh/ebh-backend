<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\Exceptions\Trip\RiderLocationNotAvailableException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\RiderLocationRepositoryInterface;
use App\Models\Trip;

/**
 * Get Rider Location Action
 *
 * Retrieves the latest location of the rider assigned to the trip
 * Only allowed for trips with DRIVER_ASSIGNED, IN_PROGRESS, or ARRIVED status
 */
readonly class GetRiderLocationAction
{
    public function __construct(
        private RiderLocationRepositoryInterface $riderLocationRepository
    ) {}

    /**
     * @throws RiderLocationNotAvailableException
     * @throws \Throwable
     */
    public function __invoke(Trip $trip): array
    {
        throw_if(
            ! $trip->canGetRiderLocation(),
            RiderLocationNotAvailableException::class
        );

        return $this->riderLocationRepository->getLatestLocationByRiderId(
            $trip->{Trip::COLUMN_ID}
        );
    }
}
