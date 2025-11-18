<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Support\Collection;

/**
 * Trip Location Formatter Service
 *
 * Handles formatting trip locations for API responses
 */
class TripLocationFormatterService
{
    /**
     * Prepare map locations (simplified for map display)
     */
    public function prepareMapLocations(Trip $trip): Collection
    {
        return $trip->locations
            ->map(fn (TripLocation $location) => [
                'latitude' => (float) $location->{TripLocation::COLUMN_LATITUDE},
                'longitude' => (float) $location->{TripLocation::COLUMN_LONGITUDE},
                'type' => $location->{TripLocation::COLUMN_TYPE},
                'sequence' => $location->{TripLocation::COLUMN_SEQUENCE},
            ])
            ->sortBy('sequence')
            ->values();
    }

    /**
     * Prepare formatted locations (structured as from/to pairs)
     */
    public function prepareFormattedLocations(Trip $trip): Collection
    {
        $formattedLocations = collect();
        $sortedLocations = $trip->locations->sortBy(TripLocation::COLUMN_SEQUENCE);

        foreach ($sortedLocations as $index => $location) {
            $nextLocation = $sortedLocations->slice($index + 1, 1)->first();

            if ($nextLocation) {
                $formattedLocations->push([
                    'from' => [
                        'location_title' => $location->{TripLocation::COLUMN_LOCATION_TITLE},
                        'location_sub_title' => $location->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                        'latitude' => (float) $location->{TripLocation::COLUMN_LATITUDE},
                        'longitude' => (float) $location->{TripLocation::COLUMN_LONGITUDE},
                        'type' => $location->{TripLocation::COLUMN_TYPE},
                    ],
                    'to' => [
                        'location_title' => $nextLocation->{TripLocation::COLUMN_LOCATION_TITLE},
                        'location_sub_title' => $nextLocation->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                        'latitude' => (float) $nextLocation->{TripLocation::COLUMN_LATITUDE},
                        'longitude' => (float) $nextLocation->{TripLocation::COLUMN_LONGITUDE},
                        'type' => $nextLocation->{TripLocation::COLUMN_TYPE},
                    ],
                ]);
            }
        }

        return $formattedLocations;
    }
}
