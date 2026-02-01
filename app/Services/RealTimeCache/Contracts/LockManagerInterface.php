<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Contracts;

/**
 * Lock Manager Interface
 *
 * Contract for distributed locking to prevent race conditions
 * Used for atomic driver assignment to trips
 */
interface LockManagerInterface
{
    /**
     * Acquire a lock
     *
     * @param  string  $key  Lock key (e.g., "rider:123" or "trip:456")
     * @param  string  $owner  Lock owner identifier
     * @param  int|null  $ttlMs  Lock TTL in milliseconds (null = use config default)
     * @return bool True if lock acquired, false if already locked
     */
    public function acquire(string $key, string $owner, ?int $ttlMs = null): bool;

    /**
     * Release a lock (only if owner matches)
     *
     * @param  string  $key  Lock key
     * @param  string  $owner  Lock owner identifier
     * @return bool True if released, false if not owner or not found
     */
    public function release(string $key, string $owner): bool;

    /**
     * Force release a lock (regardless of owner)
     *
     * @param  string  $key  Lock key
     */
    public function forceRelease(string $key): void;

    /**
     * Check if lock exists
     *
     * @param  string  $key  Lock key
     */
    public function isLocked(string $key): bool;

    /**
     * Get lock owner
     *
     * @param  string  $key  Lock key
     * @return string|null Owner or null if not locked
     */
    public function getOwner(string $key): ?string;

    /**
     * Extend lock TTL (only if owner matches)
     *
     * @param  string  $key  Lock key
     * @param  string  $owner  Lock owner identifier
     * @param  int|null  $ttlMs  New TTL in milliseconds
     * @return bool True if extended, false if not owner or not found
     */
    public function extend(string $key, string $owner, ?int $ttlMs = null): bool;

    /**
     * Acquire lock for rider assignment
     *
     * @param  int  $riderId  Rider ID
     * @param  string  $owner  Lock owner (e.g., request ID)
     * @param  int|null  $ttlMs  TTL in milliseconds
     */
    public function lockRider(int $riderId, string $owner, ?int $ttlMs = null): bool;

    /**
     * Release rider lock
     *
     * @param  int  $riderId  Rider ID
     * @param  string  $owner  Lock owner
     */
    public function unlockRider(int $riderId, string $owner): bool;

    /**
     * Acquire lock for trip
     *
     * @param  int  $tripId  Trip ID
     * @param  string  $owner  Lock owner (e.g., rider ID)
     * @param  int|null  $ttlMs  TTL in milliseconds
     */
    public function lockTrip(int $tripId, string $owner, ?int $ttlMs = null): bool;

    /**
     * Release trip lock
     *
     * @param  int  $tripId  Trip ID
     * @param  string  $owner  Lock owner
     */
    public function unlockTrip(int $tripId, string $owner): bool;
}
