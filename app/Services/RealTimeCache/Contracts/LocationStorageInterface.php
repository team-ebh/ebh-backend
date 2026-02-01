<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Contracts;

use App\Services\RealTimeCache\ValueObjects\Coordinate;
use App\Services\RealTimeCache\ValueObjects\Distance;

/**
 * Location Storage Interface
 *
 * Contract for storing and querying rider locations
 * Implementations: Redis GEO, Tile38, etc.
 */
interface LocationStorageInterface
{
    /**
     * Store rider location
     *
     * @param  int  $riderId  Rider ID
     * @param  Coordinate  $coordinate  Location coordinates
     * @param  int|null  $ttl  TTL in seconds (null = use config default)
     */
    public function store(int $riderId, Coordinate $coordinate, ?int $ttl = null): void;

    /**
     * Get rider location
     *
     * @param  int  $riderId  Rider ID
     * @return Coordinate|null Location or null if not found/expired
     */
    public function get(int $riderId): ?Coordinate;

    /**
     * Remove rider location
     *
     * @param  int  $riderId  Rider ID
     */
    public function remove(int $riderId): void;

    /**
     * Find nearby riders
     *
     * @param  Coordinate  $center  Center point to search from
     * @param  Distance  $radius  Search radius
     * @param  int|null  $limit  Maximum number of results (null = use config default)
     * @return array<int, array{rider_id: int, distance: float, coordinate: Coordinate}>
     */
    public function findNearby(Coordinate $center, Distance $radius, ?int $limit = null): array;

    /**
     * Get all rider IDs that have stored locations
     *
     * @return array<int> Array of rider IDs
     */
    public function getAllRiderIds(): array;

    /**
     * Check if rider has location stored
     *
     * @param  int  $riderId  Rider ID
     */
    public function exists(int $riderId): bool;

    /**
     * Get count of stored locations
     */
    public function count(): int;

    /**
     * Remove all locations (for cleanup/testing)
     */
    public function flush(): void;
}
