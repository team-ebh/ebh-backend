<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Contracts;

/**
 * Status Storage Interface
 *
 * Contract for storing and querying rider status (online/busy/offline)
 * Uses heartbeat pattern with TTL for automatic offline detection
 */
interface StatusStorageInterface
{
    public const string STATUS_ONLINE = 'online';

    public const string STATUS_BUSY = 'busy';

    public const string STATUS_OFFLINE = 'offline';

    /**
     * Set rider status with metadata
     *
     * @param  int  $riderId  Rider ID
     * @param  string  $status  Status (online, busy)
     * @param  array<string, mixed>  $meta  Additional metadata (vehicle_id, etc.)
     * @param  int|null  $ttl  TTL in seconds (null = use config default)
     */
    public function setStatus(int $riderId, string $status, array $meta = [], ?int $ttl = null): void;

    /**
     * Get rider status
     *
     * @param  int  $riderId  Rider ID
     * @return string|null Status or null if offline/expired
     */
    public function getStatus(int $riderId): ?string;

    /**
     * Get rider metadata
     *
     * @param  int  $riderId  Rider ID
     * @return array<string, mixed>|null Metadata or null if not found
     */
    public function getMeta(int $riderId): ?array;

    /**
     * Refresh rider TTL (heartbeat)
     *
     * @param  int  $riderId  Rider ID
     * @param  int|null  $ttl  TTL in seconds (null = use config default)
     * @return bool True if refreshed, false if rider not found
     */
    public function heartbeat(int $riderId, ?int $ttl = null): bool;

    /**
     * Set rider as online (ready to accept trips)
     *
     * @param  int  $riderId  Rider ID
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public function setOnline(int $riderId, array $meta = []): void;

    /**
     * Set rider as busy (on a trip)
     *
     * @param  int  $riderId  Rider ID
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public function setBusy(int $riderId, array $meta = []): void;

    /**
     * Set rider as offline (remove from cache)
     *
     * @param  int  $riderId  Rider ID
     */
    public function setOffline(int $riderId): void;

    /**
     * Check if rider is online (online or busy)
     *
     * @param  int  $riderId  Rider ID
     */
    public function isOnline(int $riderId): bool;

    /**
     * Check if rider is busy
     *
     * @param  int  $riderId  Rider ID
     */
    public function isBusy(int $riderId): bool;

    /**
     * Get all online rider IDs (both online and busy)
     *
     * @return array<int> Array of rider IDs
     */
    public function getOnlineRiderIds(): array;

    /**
     * Get all rider IDs that are online and ready (not busy)
     *
     * @return array<int> Array of rider IDs
     */
    public function getReadyRiderIds(): array;

    /**
     * Get all busy rider IDs
     *
     * @return array<int> Array of rider IDs
     */
    public function getBusyRiderIds(): array;

    /**
     * Get count of online riders (including busy)
     */
    public function countOnline(): int;

    /**
     * Get count of ready riders (online but not busy)
     */
    public function countReady(): int;

    /**
     * Get count of busy riders
     */
    public function countBusy(): int;

    /**
     * Remove all status data (for cleanup/testing)
     */
    public function flush(): void;
}
