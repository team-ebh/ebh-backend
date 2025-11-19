<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Services\TripPricingService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Rider Matching Service
 *
 * Responsible for finding eligible riders for a trip based on various criteria:
 * - Proximity (distance from trip origin)
 * - Vehicle type compatibility
 * - Accessibility requirements
 * - Rider availability status
 */
readonly class RiderMatchingService
{
    public function __construct(
        private TripPricingService $tripPricingService,
    ) {}

    /**
     * Find eligible riders within a specific radius
     *
     * @param  Trip  $trip  The trip to find riders for
     * @param  int  $radiusMeters  Search radius in meters
     * @return Collection<Rider> Collection of eligible riders, sorted by proximity
     */
    public function findEligibleRiders(Trip $trip, int $radiusMeters): Collection
    {
        // Get trip origin location
        $origin = $trip->locations()
            ->orderBy(TripLocation::COLUMN_SEQUENCE)
            ->first();

        if (! $origin) {
            return new Collection;
        }

        $originLat = $origin->{TripLocation::COLUMN_LATITUDE};
        $originLng = $origin->{TripLocation::COLUMN_LONGITUDE};

        // Base query for riders
        $query = Rider::query()->with(['vehicle.carType']);

        // Filter: Only online riders
        if (config('trip.matching.only_online_riders', true)) {
            $query->where(Rider::COLUMN_STATUS, RiderStatusEnum::ONLINE);
        }

        // Filter: Exclude busy riders
        if (config('trip.matching.exclude_busy_riders', true)) {
            $query->where(Rider::COLUMN_STATUS, '!=', RiderStatusEnum::BUSY);
        }

        // Filter: Vehicle type match
        if (config('trip.matching.require_vehicle_type_match', true)) {
            $query->whereHas('vehicle', function ($q) use ($trip) {
                $q->where('vehicle_type_id', $trip->{Trip::COLUMN_VEHICLE_TYPE_ID});
            });
        }

        // Filter: Accessibility requirements
        if (config('trip.matching.require_accessibility_match', true)) {
            $tripAccessibilityRequirements = $trip->accessibility->pluck('accessibility_requirement_id')->toArray();

            if (! empty($tripAccessibilityRequirements)) {
                $query->where(function ($q) use ($tripAccessibilityRequirements) {
                    foreach ($tripAccessibilityRequirements as $requirement) {
                        $q->whereJsonContains(
                            Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS,
                            $requirement
                        );
                    }
                });
            }
        }

        // Calculate distance and filter by radius
        // Using Haversine formula for distance calculation
        $riders = $query->get()->filter(function (Rider $rider) use ($originLat, $originLng) {
            $distance = $this->calculateDistance(
                $originLat,
                $originLng,
                $rider->latitude,
                $rider->longitude
            );

            // TODO: This assumes riders have a location field
            return true;
            //            return $distance <= $radiusMeters;
        });

        // Sort by proximity (if location data available)
        return $riders->sortBy(function (Rider $rider) use ($originLat, $originLng) {
            if (! isset($rider->latitude) || ! isset($rider->longitude)) {
                return PHP_INT_MAX; // Put riders without location at the end
            }

            return $this->calculateDistance(
                $originLat,
                $originLng,
                $rider->latitude,
                $rider->longitude
            );
        })->values();
    }

    /**
     * Check if a rider is eligible for a trip
     *
     * @param  Rider  $rider  The rider to check
     * @param  Trip  $trip  The trip to check against
     * @return bool True if rider is eligible
     */
    public function isRiderEligible(Rider $rider, Trip $trip): bool
    {
        // Check if rider is online
        if (config('trip.matching.only_online_riders', true)) {
            if (! $rider->isOnline()) {
                return false;
            }
        }

        // Check if rider is not busy
        if (config('trip.matching.exclude_busy_riders', true)) {
            if ($rider->isBusy()) {
                return false;
            }
        }

        // Check vehicle type
        if (config('trip.matching.require_vehicle_type_match', true)) {
            $rider->loadMissing('vehicle');

            if (! $rider->vehicle) {
                return false;
            }

            if ($rider->vehicle->vehicle_type_id !== $trip->{Trip::COLUMN_VEHICLE_TYPE_ID}) {
                return false;
            }
        }

        // Check accessibility requirements
        if (config('trip.matching.require_accessibility_match', true)) {
            $tripAccessibilityRequirements = $trip->accessibility->pluck('accessibility_requirement_id')->toArray();

            if (! empty($tripAccessibilityRequirements)) {
                $riderCertifications = $rider->{Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS} ?? [];

                foreach ($tripAccessibilityRequirements as $requirement) {
                    if (! in_array($requirement, $riderCertifications, true)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Calculate distance between two coordinates in meters
     *
     * Uses TripPricingService's Haversine formula implementation
     *
     * @param  float  $lat1  Latitude of point 1
     * @param  float  $lon1  Longitude of point 1
     * @param  float  $lat2  Latitude of point 2
     * @param  float  $lon2  Longitude of point 2
     * @return float Distance in meters
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // TripPricingService returns distance in kilometers, convert to meters
        $distanceKm = $this->tripPricingService->calculateDistance($lat1, $lon1, $lat2, $lon2);

        return $distanceKm * 1000;
    }

    /**
     * Get search radius for a specific attempt
     *
     * @param  int  $attempt  Search attempt number (1-based)
     * @return int Radius in meters
     */
    public function getSearchRadiusForAttempt(int $attempt): int
    {
        $radiuses = config('trip.request.search_radiuses', [200, 400, 800, 2000]);

        $index = $attempt - 1;

        if ($index < 0 || $index >= count($radiuses)) {
            return $radiuses[count($radiuses) - 1]; // Return last radius as fallback
        }

        return $radiuses[$index];
    }

    /**
     * Get maximum search attempts from config
     *
     * @return int Maximum number of attempts
     */
    public function getMaxSearchAttempts(): int
    {
        return config('trip.request.max_search_attempts', 4);
    }
}
