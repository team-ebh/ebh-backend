<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer\Trip;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use App\Models\Trip;

readonly class CustomerTripRepository implements CustomerTripRepositoryInterface
{
    /**
     * Get customer's active trip (not draft/completed/cancelled)
     */
    public function getActiveTrip(int $customerId): ?Trip
    {
        return Trip::query()
            ->forCustomer($customerId)
            ->activeTrips()
            ->with([
                'rider:id,full_name,phone_number',
                'rider.accessibilityCertifications:id,rider_id,certification_type',
                'rider.vehicle:id,rider_id,car_make_id,car_model_id,plate_number',
                'rider.vehicle.carMake:id,name,name_ar',
                'rider.vehicle.carModel:id,name,name_ar',
            ])
            ->first();
    }

    /**
     * Check if customer has an active trip
     */
    public function existsActiveTrip(int $customerId): bool
    {
        return Trip::query()
            ->forCustomer($customerId)
            ->activeTrips()
            ->exists();
    }

    /**
     * Get customer's scheduled trip (DRAFT with SCHEDULED type)
     */
    public function getScheduledTrip(int $customerId): ?Trip
    {
        return Trip::query()
            ->forCustomer($customerId)
            ->scheduledTrips()
            ->with(['locations:id,trip_id,location_title,latitude,longitude,type,sequence'])
            ->first();
    }

    /**
     * Check if customer has a scheduled trip
     */
    public function existsScheduledTrip(int $customerId): bool
    {
        return Trip::query()
            ->forCustomer($customerId)
            ->scheduledTrips()
            ->exists();
    }

    /**
     * Check if customer has a scheduled trip
     */
    public function firstScheduledTrip(int $customerId): ?Trip
    {
        return Trip::query()
            ->forCustomer($customerId)
            ->scheduledTrips()
            ->first();
    }
}
