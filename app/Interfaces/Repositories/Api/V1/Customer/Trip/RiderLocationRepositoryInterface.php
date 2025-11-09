<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer\Trip;

interface RiderLocationRepositoryInterface
{
    public function getLatestLocationByRiderId(int $tripId): array;
}
