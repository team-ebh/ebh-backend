<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\TripLocation;

/**
 * Trip Action Service
 *
 * Calculates the next available action for a trip based on current location statuses
 */
readonly class TripActionService
{
    /**
     * Get next action for trip
     *
     * Returns: 'arrived', 'pickup', 'complete', or null if no action available
     */
    public function getNextAction(Trip $trip): ?string
    {
        // TODO:: this functionality must be changed based on trip rider type 2, and 3
        $locations = $trip->locations()->orderBy(TripLocation::COLUMN_SEQUENCE)->get();

        if ($locations->isEmpty()) {
            return null;
        }

        // Find current location (first non-finished location)
        $currentLocation = $locations->first(fn (TripLocation $loc) => ! $loc->isFinished());

        if (! $currentLocation) {
            // All locations finished
            return null;
        }

        // For ORIGIN locations
        if ($currentLocation->isOrigin()) {
            // If pending, next action is 'arrived'
            if ($currentLocation->isPending()) {
                return 'arrived';
            }

            // If arrived, next action is 'pickup'
            if ($currentLocation->isArrived()) {
                return 'pickup';
            }

            // If picked up, next action is 'complete'
            if ($currentLocation->isPickedUp()) {
                return 'complete';
            }
        }

        // For DESTINATION locations
        if ($currentLocation->isDestination()) {
            // If pending, next action is 'complete'
            if ($currentLocation->isPending()) {
                return 'complete';
            }
        }

        return null;
    }

    /**
     * Get current active location
     *
     * Returns the first location that is not finished
     * All locations are considered finished when status is COMPLETED
     */
    public function getCurrentLocation(Trip $trip): ?TripLocation
    {
        $locations = $trip->locations()->orderBy(TripLocation::COLUMN_SEQUENCE)->get();

        return $locations->first(fn (TripLocation $loc) => ! $loc->isFinished());
    }

    /**
     * Validate if location can be marked as arrived
     */
    public function validateCanArrive(?TripLocation $location): bool
    {
        return $location && $location->isPending();
    }

    /**
     * Validate if location can be picked up
     */
    public function validateCanPickUp(?TripLocation $location): bool
    {
        return $location
            && $location->isArrived()
            && $location->isOrigin();
    }

    /**
     * Validate if location can be completed
     */
    public function validateCanComplete(?TripLocation $location): bool
    {
        if (! $location) {
            return false;
        }

        return $location->isDestination() && $location->isPending();
    }
}
