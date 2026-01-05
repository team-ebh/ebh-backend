<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Enums\Trip\TripActionEnum;
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
     * Returns: TripActionEnum::ARRIVED, TripActionEnum::PICKUP, TripActionEnum::COMPLETE, or null if no action available
     */
    public function getNextAction(Trip $trip): ?TripActionEnum
    {
        $locations = $trip->loadMissing('locations')->locations;

        if ($locations->isEmpty()) {
            return null;
        }

        // For ROUND_TRIP_WAIT trips, check if we need to pick up passenger again at a dropped-off destination
        if ($trip->isRoundTripWithWait()) {
            $waitingLocation = $this->getWaitingPickupLocation($trip);
            if ($waitingLocation) {
                return $this->getNextActionForWaitingLocation($waitingLocation);
            }
        }

        // Find current location (first non-finished location)
        $currentLocation = $this->getCurrentLocation($trip);

        if (! $currentLocation) {
            // All locations finished
            return null;
        }

        // For ORIGIN locations
        if ($currentLocation->isOrigin()) {
            // If pending, next action is 'arrived'
            if ($currentLocation->isPending()) {
                return TripActionEnum::ARRIVED;
            }

            // If arrived, next action is 'pickup'
            if ($currentLocation->isArrived()) {
                return TripActionEnum::PICKUP;
            }

            // If picked up, next action is 'complete'
            if ($currentLocation->isPickedUp()) {
                return TripActionEnum::COMPLETE;
            }
        }

        // For DESTINATION locations
        if ($currentLocation->isDestination()) {
            // If pending, next action is 'complete'
            if ($currentLocation->isPending()) {
                return TripActionEnum::COMPLETE;
            }

            // If picked up, next action is 'complete' (for ROUND_TRIP_WAIT after pickup at previous destination)
            if ($currentLocation->isPickedUp()) {
                return TripActionEnum::COMPLETE;
            }
        }

        return null;
    }

    /**
     * Get the waiting pickup location for ROUND_TRIP_WAIT trips
     *
     * Returns a destination location that is DROPPED_OFF
     * (meaning the customer was dropped off and is waiting to be picked up again)
     */
    public function getWaitingPickupLocation(Trip $trip): ?TripLocation
    {
        if (! $trip->isRoundTripWithWait()) {
            return null;
        }

        $locations = $trip->loadMissing('locations')->locations;
        $lastLocation = $locations->last();

        // If any destination is already PICKED_UP, don't return waiting location
        // (customer is already picked up, should complete the picked up destination)
        $hasPickedUpDestination = $locations->contains(function (TripLocation $loc) {
            return $loc->isDestination() && $loc->isPickedUp();
        });

        if ($hasPickedUpDestination) {
            return null;
        }

        // Find a destination that is dropped off (waiting for pickup)
        // but is NOT the last destination
        return $locations->first(function (TripLocation $loc) use ($lastLocation) {
            if (! $loc->isDestination()) {
                return false;
            }

            // Skip if this is the last location
            if ($loc->{TripLocation::COLUMN_ID} === $lastLocation->{TripLocation::COLUMN_ID}) {
                return false;
            }

            // Check if location is in "waiting for pickup" state (DROPPED_OFF)
            return $loc->isDroppedOff();
        });
    }

    /**
     * Get next action for a waiting pickup location (ROUND_TRIP_WAIT)
     * After drop off, next action is pickup (no need for arrived, rider is already there)
     */
    private function getNextActionForWaitingLocation(TripLocation $location): TripActionEnum
    {
        return TripActionEnum::PICKUP;
    }

    /**
     * Get current active location
     *
     * Returns the first location that is not finished
     * All locations are considered finished when status is COMPLETED
     */
    public function getCurrentLocation(Trip $trip): ?TripLocation
    {
        $locations = $trip->loadMissing('locations')->locations;

        return $locations->first(fn (TripLocation $loc) => ! $loc->isFinished());
    }

    /**
     * Validate if location can be marked as arrived
     */
    public function validateCanArrive(?TripLocation $location): bool
    {
        return $location && $location->isPending() && $location->isOrigin();
    }

    /**
     * Validate if location can be picked up
     */
    public function validateCanPickUp(?TripLocation $location, ?Trip $trip = null): bool
    {
        if (! $location) {
            return false;
        }

        // For ROUND_TRIP_WAIT, allow pickup at dropped-off destination (waiting for pickup)
        if ($trip?->isRoundTripWithWait() && $location->isDestination() && $location->isDroppedOff()) {
            return true;
        }

        // Standard pickup: origin location that has arrived status
        return $location->isArrived() && $location->isOrigin();
    }

    /**
     * Validate if location can be completed
     */
    public function validateCanComplete(?TripLocation $location): bool
    {
        if (! $location) {
            return false;
        }

        return $location->isDestination() && ($location->isPending() || $location->isDroppedOff() || $location->isPickedUp());
    }

    /**
     * Get last location
     */
    public function getLastLocation(Trip $trip): ?TripLocation
    {
        return $trip->loadMissing('locations')->locations?->last();
    }

    public function isSameLocation(TripLocation $firstLocation, TripLocation $secondLocation): bool
    {
        return $firstLocation->{TripLocation::COLUMN_ID} === $secondLocation->{TripLocation::COLUMN_ID};
    }
}
