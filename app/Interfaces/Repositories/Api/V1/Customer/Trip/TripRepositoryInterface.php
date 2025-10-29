<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, float $baseFare, float $estimatedPrice): Trip;

    public function attachAccessibilityRequirements(Trip $trip, array $requirements): void;

    public function findById(int $id): ?Trip;

    public function getCustomerTrips(int $customerId): Collection;

    public function updateStatus(Trip $trip, string $status): Trip;
}
