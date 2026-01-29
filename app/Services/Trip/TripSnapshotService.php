<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use App\Services\DistanceCalculationService;
use Carbon\CarbonInterface;

/**
 * Service for managing trip snapshots and timing data
 *
 * Handles:
 * - Vehicle snapshot at trip acceptance
 * - Picked up timestamp
 * - Completed timestamp and duration calculation
 * - Distance calculation between locations
 */
readonly class TripSnapshotService
{
    public function __construct(
        private DistanceCalculationService $distanceCalculationService,
    ) {}

    /**
     * Create vehicle snapshot when rider accepts trip
     *
     * @return array<string, mixed>|null
     */
    public function createVehicleSnapshot(Rider $rider): ?array
    {
        $vehicle = $rider->vehicle()
            ->with(['carType', 'carColor', 'carMake', 'carModel', 'passengerCapacity'])
            ->first();

        if (! $vehicle) {
            return null;
        }

        return [
            'id' => $vehicle->{Vehicle::COLUMN_ID},
            'car_type' => $vehicle->carType?->translated(VehicleSetting::COLUMN_NAME),
            'car_color' => $vehicle->carColor?->translated(VehicleSetting::COLUMN_NAME),
            'car_make' => $vehicle->carMake?->translated(VehicleSetting::COLUMN_NAME),
            'car_model' => $vehicle->carModel?->translated(VehicleSetting::COLUMN_NAME),
            'model' => $vehicle->getModelCar(),
            'year' => $vehicle->{Vehicle::COLUMN_YEAR},
            'plate_number' => $vehicle->{Vehicle::COLUMN_PLATE_NUMBER},
            'passenger_capacity' => $vehicle->passengerCapacity?->{VehicleSetting::COLUMN_CAPACITY},
        ];
    }

    /**
     * Calculate duration in minutes between picked_up_at and completed_at
     */
    public function calculateDurationMinutes(CarbonInterface $pickedUpAt, CarbonInterface $completedAt): int
    {
        return (int) ceil($pickedUpAt->diffInMinutes($completedAt));
    }

    /**
     * Calculate total distance in meters between all trip locations
     *
     * Sums distances between consecutive locations:
     * Location 1 -> Location 2 + Location 2 -> Location 3, etc.
     */
    public function calculateTotalDistanceMeters(Trip $trip): int
    {
        $locations = $trip->locations;

        if ($locations->count() < 2) {
            return 0;
        }

        $totalDistance = 0;

        for ($i = 0; $i < $locations->count() - 1; $i++) {
            $origin = $locations[$i];
            $destination = $locations[$i + 1];

            $result = $this->distanceCalculationService->calculateDistanceAndDuration(
                originLatitude: (float) $origin->{TripLocation::COLUMN_LATITUDE},
                originLongitude: (float) $origin->{TripLocation::COLUMN_LONGITUDE},
                destinationLatitude: (float) $destination->{TripLocation::COLUMN_LATITUDE},
                destinationLongitude: (float) $destination->{TripLocation::COLUMN_LONGITUDE},
            );

            $totalDistance += $result['distance_meters'] ?? 0;
        }

        return $totalDistance;
    }

    /**
     * Calculate trip duration and distance
     *
     * Calculates duration from picked_up_at to now, and total distance
     * between all locations. Returns the data without saving to database.
     *
     * @return array{duration_minutes: int, distance_meters: int}
     */
    public function calculateDurationAndDistance(Trip $trip): array
    {
        $pickedUpAt = $trip->{Trip::COLUMN_PICKED_UP_AT};

        // Calculate duration in minutes (0 if picked_up_at is not set)
        $durationMinutes = $pickedUpAt
            ? $this->calculateDurationMinutes($pickedUpAt, now())
            : 0;

        // Calculate total distance between all locations
        $distanceMeters = $this->calculateTotalDistanceMeters($trip);

        return [
            'duration_minutes' => $durationMinutes,
            'distance_meters' => $distanceMeters,
        ];
    }
}
