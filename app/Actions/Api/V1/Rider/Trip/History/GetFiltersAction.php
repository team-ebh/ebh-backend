<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip\History;

use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;

readonly class GetFiltersAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    public function __invoke(int $riderId): array
    {
        return [
            'total_rides_count' => $this->riderTripRepository->getTotalRidesCount($riderId),
            'canceled_count' => $this->riderTripRepository->getCanceledCount($riderId),
        ];
    }
}
