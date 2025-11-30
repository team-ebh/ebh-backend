<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Location;

use App\DTOs\Api\V1\Rider\Location\UpdateLocationDTO;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;

readonly class UpdateLocationAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository
    ) {}

    /**
     * Update rider's location
     */
    public function __invoke(UpdateLocationDTO $dto): void
    {
        $this->riderRepository->updateLocation(
            $dto->rider,
            $dto->latitude,
            $dto->longitude
        );
    }
}
