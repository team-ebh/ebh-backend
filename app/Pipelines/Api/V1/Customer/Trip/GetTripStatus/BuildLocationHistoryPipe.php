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

        // Build locations array from trip locations
        $context->locations = $context->trip->locations
            ->sortBy(TripLocation::COLUMN_SEQUENCE)
            ->map(function (TripLocation $location) {
                return [
                    'id' => $location->{TripLocation::COLUMN_ID},
                    'type' => $location->{TripLocation::COLUMN_TYPE},
                    'status' => $location->{TripLocation::COLUMN_STATUS},
                    'location_title' => $location->{TripLocation::COLUMN_LOCATION_TITLE},
                    'location_sub_title' => $location->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                    'latitude' => $location->{TripLocation::COLUMN_LATITUDE},
                    'longitude' => $location->{TripLocation::COLUMN_LONGITUDE},
                    'sequence' => $location->{TripLocation::COLUMN_SEQUENCE},
                ];
            })
            ->values()
            ->toArray();

        return $next($context);
    }
}
