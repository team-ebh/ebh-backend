<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer\Trip;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\RiderLocationRepositoryInterface;

class RiderLocationRepository implements RiderLocationRepositoryInterface
{
    public function getLatestLocationByRiderId(int $tripId): array
    {
        return [
            'latitude' => 29.353325,
            'longitude' => 47.98227,
        ];
    }
}
