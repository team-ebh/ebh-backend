<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Rider\Trip;

use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Database\Eloquent\Collection;

interface RiderTripRepositoryInterface
{
    public function getRider(int $riderId): Rider;

    public function getRiderWithVehicle(int $riderId): Rider;

    public function getTripRequests(int $riderId): Collection;

    public function getActiveTrip(int $riderId): ?Trip;

    public function acceptTripRequestWithLock(TripRequest $tripRequest, Rider $rider): Trip;

    public function declineTripRequest(TripRequest $tripRequest): void;

    public function cancelTripRequestWithLock(TripRequest $tripRequest): Trip;
}
