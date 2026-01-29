<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Earnings;

use App\DTOs\Api\V1\Rider\Earnings\GetEarningsLatestTripsDTO;
use App\Interfaces\Repositories\Api\V1\Rider\Earnings\RiderEarningsRepositoryInterface;

readonly class GetEarningsLatestTripsAction
{
    public function __construct(
        private RiderEarningsRepositoryInterface $riderEarningsRepository,
    ) {}

    public function __invoke(GetEarningsLatestTripsDTO $dto): array
    {
        $trips = $this->riderEarningsRepository->getLatestTrips($dto->riderId);

        return getInfiniteScroll($trips);
    }
}
