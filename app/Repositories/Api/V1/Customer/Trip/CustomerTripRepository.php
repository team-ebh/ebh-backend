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
                'rider:id,full_name,phone_number,accessibility_certifications',
                'rider.vehicle:id,rider_id,car_make_id,car_model_id,plate_number',
                'rider.vehicle.carMake:id,name,name_ar',
                'rider.vehicle.carModel:id,name,name_ar',
            ])
            ->first();
    }
}
