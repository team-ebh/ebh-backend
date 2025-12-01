<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer\Trip;

use App\Models\Trip;

interface CustomerTripRepositoryInterface
{
    public function getActiveTrip(int $customerId): ?Trip;
}
