<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\Models\TripLocation;
use Closure;

/**
 * Build Location History Pipe
 *
 * Builds location history from trip locations with sequences
 */
class BuildLocationHistoryPipe
{
    public function handle(TripStatusContext $context, Closure $next): mixed
    {
        if (! $context->found) {
            return $next($context);
        }

        // Load trip locations if not already loaded
        if (! $context->trip->relationLoaded('locations')) {
            $context->trip->load(['locations' => fn ($q) => $q->select([
                'id',
                'trip_id',
                'type',
                'status',
                'location_title',
                'location_sub_title',
                'latitude',
                'longitude',
                'sequence',
            ])]);
        }

        $sortedLocations = $context->trip->locations
            ->sortBy(TripLocation::COLUMN_SEQUENCE)
            ->values();

        // Build map locations (coordinates only for map display)
        $context->mapLocations = $sortedLocations
            ->map(function (TripLocation $location) {
                return [
                    'latitude' => $location->{TripLocation::COLUMN_LATITUDE},
                    'longitude' => $location->{TripLocation::COLUMN_LONGITUDE},
                    'type' => $location->{TripLocation::COLUMN_TYPE},
                    'sequence' => $location->{TripLocation::COLUMN_SEQUENCE},
                ];
            })
            ->toArray();

        // Build formatted locations (from/to structure)
        $formattedLocations = [];
        for ($i = 0; $i < $sortedLocations->count() - 1; $i++) {
            $from = $sortedLocations[$i];
            $to = $sortedLocations[$i + 1];

            $formattedLocations[] = [
                'is_active' => true,
                'from' => [
                    'location_title' => $from->{TripLocation::COLUMN_LOCATION_TITLE},
                    'location_sub_title' => $from->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                    'latitude' => $from->{TripLocation::COLUMN_LATITUDE},
                    'longitude' => $from->{TripLocation::COLUMN_LONGITUDE},
                ],
                'to' => [
                    'location_title' => $to->{TripLocation::COLUMN_LOCATION_TITLE},
                    'location_sub_title' => $to->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                    'latitude' => $to->{TripLocation::COLUMN_LATITUDE},
                    'longitude' => $to->{TripLocation::COLUMN_LONGITUDE},
                ],
            ];
        }
        $context->formattedLocations = $formattedLocations;

        return $next($context);
    }
}
