<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache;

use App\Services\RealTimeCache\Contracts\LocationStorageInterface;
use App\Services\RealTimeCache\Contracts\LockManagerInterface;
use App\Services\RealTimeCache\Contracts\StatusStorageInterface;
use App\Services\RealTimeCache\Contracts\TripStorageInterface;
use App\Services\RealTimeCache\ValueObjects\Coordinate;
use App\Services\RealTimeCache\ValueObjects\Distance;

/**
 * RealTime Cache Manager
 *
 * Main entry point for all real-time cache operations.
 * Provides access to location, status, trip, and lock services.
 *
 * Usage:
 * ```php
 * $cache = app(RealTimeCacheManager::class);
 *
 * // Store rider location
 * $cache->location()->store($riderId, Coordinate::make($lat, $lng));
 *
 * // Find nearby riders
 * $nearby = $cache->location()->findNearby(
 *     Coordinate::make($lat, $lng),
 *     Distance::meters(5000)
 * );
 *
 * // Set rider status
 * $cache->status()->setOnline($riderId, ['vehicle_id' => 1]);
 *
 * // Store trip data
 * $cache->trip()->store($tripId, $tripData, 'pending');
 *
 * // Lock rider for assignment
 * if ($cache->lock()->lockRider($riderId, $requestId)) {
 *     // Assign rider
 *     $cache->lock()->unlockRider($riderId, $requestId);
 * }
 * ```
 */
readonly class RealTimeCacheManager
{
    public function __construct(
        private LocationStorageInterface $locationStorage,
        private StatusStorageInterface $statusStorage,
        private TripStorageInterface $tripStorage,
        private LockManagerInterface $lockManager,
    ) {}

    /**
     * Access rider location storage
     */
    public function location(): LocationStorageInterface
    {
        return $this->locationStorage;
    }

    /**
     * Access rider status storage
     */
    public function status(): StatusStorageInterface
    {
        return $this->statusStorage;
    }

    /**
     * Access trip cache storage
     */
    public function trip(): TripStorageInterface
    {
        return $this->tripStorage;
    }

    /**
     * Access lock manager
     */
    public function lock(): LockManagerInterface
    {
        return $this->lockManager;
    }

    /**
     * Find nearby online riders
     *
     * Combines location and status queries into a single convenient method.
     *
     * @param  Coordinate  $center  Center point to search from
     * @param  Distance  $radius  Search radius
     * @param  bool  $excludeBusy  If true, excludes busy riders (default: false)
     * @param  int|null  $limit  Maximum number of results
     * @return array<int, array{rider_id: int, distance: float, coordinate: Coordinate, status: string}>
     */
    public function findNearbyOnlineRiders(
        Coordinate $center,
        Distance $radius,
        bool $excludeBusy = false,
        ?int $limit = null
    ): array {
        $nearbyRiders = $this->locationStorage->findNearby($center, $radius, $limit);

        if (empty($nearbyRiders)) {
            return [];
        }

        $onlineRiders = [];

        foreach ($nearbyRiders as $rider) {
            $riderId = $rider['rider_id'];
            $status = $this->statusStorage->getStatus($riderId);

            // Skip offline riders
            if ($status === null) {
                continue;
            }

            // Skip busy riders if requested
            if ($excludeBusy && $status === StatusStorageInterface::STATUS_BUSY) {
                continue;
            }

            $rider['status'] = $status;
            $onlineRiders[] = $rider;
        }

        return $onlineRiders;
    }

    /**
     * Get statistics for monitoring
     *
     * @return array{
     *     locations: array{count: int},
     *     status: array{online: int, ready: int, busy: int},
     *     trips: array{count: int},
     * }
     */
    public function getStats(): array
    {
        return [
            'locations' => [
                'count' => $this->locationStorage->count(),
            ],
            'status' => [
                'online' => $this->statusStorage->countOnline(),
                'ready' => $this->statusStorage->countReady(),
                'busy' => $this->statusStorage->countBusy(),
            ],
            'trips' => [
                'count' => $this->tripStorage->count(),
            ],
        ];
    }

    /**
     * Flush all cache data (use with caution!)
     */
    public function flushAll(): void
    {
        $this->locationStorage->flush();
        $this->statusStorage->flush();
        $this->tripStorage->flush();
    }
}
