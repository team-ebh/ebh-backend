<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip\History;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;

/**
 * Get Past Trips Action
 *
 * Retrieves past (completed/cancelled) trips for a customer
 */
readonly class GetPastTripsAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    public function __invoke(int $customerId): array
    {
        return getInfiniteScroll($this->tripRepository->getPastTrips($customerId));
    }
}
