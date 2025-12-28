<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer\Trip;

use App\Exceptions\Rider\LocationNotAvailableException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\RiderLocationRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;

class RiderLocationRepository implements RiderLocationRepositoryInterface
{
    /**
     * Get the latest location of the rider assigned to a trip
     *
     * @throws LocationNotAvailableException
     * @throws \Throwable
     */
    public function getLatestLocationByRiderId(int $tripId): array
    {
        $trip = Trip::query()
            ->select([Trip::COLUMN_ID, Trip::COLUMN_RIDER_ID])
            ->find($tripId);

        throw_if(
            ! $trip || ! $trip->{Trip::COLUMN_RIDER_ID},
            LocationNotAvailableException::class
        );

        $rider = Rider::query()
            ->select([
                Rider::COLUMN_ID,
                Rider::COLUMN_LATITUDE,
                Rider::COLUMN_LONGITUDE,
                Rider::COLUMN_LAST_LOCATION_UPDATE,
            ])
            ->find($trip->{Trip::COLUMN_RIDER_ID});

        throw_if(
            ! $rider || ! $rider->{Rider::COLUMN_LATITUDE} || ! $rider->{Rider::COLUMN_LONGITUDE},
            LocationNotAvailableException::class
        );

        return [
            'latitude' => $rider->{Rider::COLUMN_LATITUDE},
            'longitude' => $rider->{Rider::COLUMN_LONGITUDE},
        ];
    }
}
