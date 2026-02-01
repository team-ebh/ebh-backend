<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Contracts;

/**
 * Trip Storage Interface
 *
 * Contract for caching trip data during active rides
 * All data is ephemeral and synced back to DB on completion
 */
interface TripStorageInterface
{
    /**
     * Store trip data
     *
     * @param  int  $tripId  Trip ID
     * @param  array<string, mixed>  $data  Trip data
     * @param  string  $status  Trip status (for TTL selection)
     */
    public function store(int $tripId, array $data, string $status = 'pending'): void;

    /**
     * Get trip data
     *
     * @param  int  $tripId  Trip ID
     * @return array<string, mixed>|null Trip data or null if not found
     */
    public function get(int $tripId): ?array;

    /**
     * Update trip data (merge with existing)
     *
     * @param  int  $tripId  Trip ID
     * @param  array<string, mixed>  $data  Data to merge
     */
    public function update(int $tripId, array $data): void;

    /**
     * Update trip status and refresh TTL
     *
     * @param  int  $tripId  Trip ID
     * @param  string  $status  New status
     */
    public function updateStatus(int $tripId, string $status): void;

    /**
     * Remove trip data
     *
     * @param  int  $tripId  Trip ID
     */
    public function remove(int $tripId): void;

    /**
     * Check if trip exists in cache
     *
     * @param  int  $tripId  Trip ID
     */
    public function exists(int $tripId): bool;

    /**
     * Set rider's current active trip
     *
     * @param  int  $riderId  Rider ID
     * @param  int  $tripId  Trip ID
     */
    public function setRiderActiveTrip(int $riderId, int $tripId): void;

    /**
     * Get rider's current active trip
     *
     * @param  int  $riderId  Rider ID
     * @return int|null Trip ID or null
     */
    public function getRiderActiveTrip(int $riderId): ?int;

    /**
     * Clear rider's active trip
     *
     * @param  int  $riderId  Rider ID
     */
    public function clearRiderActiveTrip(int $riderId): void;

    /**
     * Set customer's current active trip
     *
     * @param  int  $customerId  Customer ID
     * @param  int  $tripId  Trip ID
     */
    public function setCustomerActiveTrip(int $customerId, int $tripId): void;

    /**
     * Get customer's current active trip
     *
     * @param  int  $customerId  Customer ID
     * @return int|null Trip ID or null
     */
    public function getCustomerActiveTrip(int $customerId): ?int;

    /**
     * Clear customer's active trip
     *
     * @param  int  $customerId  Customer ID
     */
    public function clearCustomerActiveTrip(int $customerId): void;

    /**
     * Get all cached trip IDs
     *
     * @return array<int> Array of trip IDs
     */
    public function getAllTripIds(): array;

    /**
     * Get count of cached trips
     */
    public function count(): int;

    /**
     * Remove all trip data (for cleanup/testing)
     */
    public function flush(): void;
}
