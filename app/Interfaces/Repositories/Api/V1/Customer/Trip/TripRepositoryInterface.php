<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, ?float $accessibilityCost, float $totalPrice): Trip;

    public function attachAccessibilityRequirements(Trip $trip, array $requirements): void;

    public function findById(int $id): ?Trip;

    public function getCustomerTrips(int $customerId): Collection;

    public function updateStatus(Trip $trip, TripStatusEnum $status): Trip;

    public function updateTripPrices(Trip $trip, ?float $accessibilityPrice, ?float $waitingPrice, float $totalPrice): Trip;

    public function updateDestinationLocation(Trip $trip, ?string $locationTitle, ?string $locationSubTitle, float $latitude, float $longitude): void;
}
