<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip\History;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;

/**
 * Get Upcoming Trips Action
 *
 * Retrieves upcoming (scheduled) trips for a customer
 */
readonly class GetUpcomingTripsAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    public function __invoke(int $customerId): array
    {
        return getInfiniteScroll($this->tripRepository->getUpcomingTrips($customerId));
    }
}
