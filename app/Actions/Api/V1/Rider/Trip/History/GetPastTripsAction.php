<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip\History;

use App\DTOs\Api\V1\Rider\Trip\History\GetPastTripsDTO;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;

/**
 * Get Past Trips Action
 *
 * Retrieves past (completed/cancelled) trips for a rider with optional filtering
 */
readonly class GetPastTripsAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository
    ) {}

    public function __invoke(GetPastTripsDTO $dto): array
    {
        return getInfiniteScroll($this->riderTripRepository->getPastTrips($dto->riderId, $dto->filter));
    }
}
