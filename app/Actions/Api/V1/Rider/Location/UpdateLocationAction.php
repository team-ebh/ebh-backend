<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Location;

use App\DTOs\Api\V1\Rider\Location\UpdateLocationDTO;
use App\Events\Socket\Customer\RiderLocationUpdatedEvent;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use App\Services\Cache\RiderCache;

readonly class UpdateLocationAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * Update rider's location and broadcast to customer if on active trip
     */
    public function __invoke(UpdateLocationDTO $dto): void
    {
        // Update rider location
        $this->riderRepository->updateLocation(
            $dto->rider,
            $dto->latitude,
            $dto->longitude
        );

        // Clear rider cache to update location in cache and GEO set
        RiderCache::forgetRider($dto->rider->{Rider::COLUMN_ID});

        // Check if rider has an active trip and broadcast location update
        $activeTrip = $this->riderTripRepository->getActiveTrip($dto->rider->{Rider::COLUMN_ID});

        if ($activeTrip && $activeTrip->canGetRiderLocation()) {
            broadcast(new RiderLocationUpdatedEvent(
                customerId: $activeTrip->{Trip::COLUMN_CUSTOMER_ID},
                tripId: $activeTrip->{Trip::COLUMN_ID},
                riderId: $dto->rider->{Rider::COLUMN_ID},
                latitude: $dto->latitude,
                longitude: $dto->longitude,
            ));
        }
    }
}
