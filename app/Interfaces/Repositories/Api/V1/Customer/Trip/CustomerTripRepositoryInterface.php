<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer\Trip;

use App\Models\Trip;

interface CustomerTripRepositoryInterface
{
    public function getActiveTrip(int $customerId): ?Trip;

    public function existsActiveTrip(int $customerId): bool;

    public function getScheduledTrip(int $customerId): ?Trip;

    public function existsScheduledTrip(int $customerId): bool;
}
