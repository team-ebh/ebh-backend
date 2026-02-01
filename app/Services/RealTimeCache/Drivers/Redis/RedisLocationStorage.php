<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Redis;

use App\Services\RealTimeCache\Contracts\LocationStorageInterface;
use App\Services\RealTimeCache\ValueObjects\Coordinate;
use App\Services\RealTimeCache\ValueObjects\Distance;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

/**
 * Redis Location Storage
 *
 * Uses Redis GEO commands for efficient geospatial queries
 * Combined with a tracking SET for TTL-based cleanup
 *
 * Keys:
 * - {prefix}geo:riders -> GEO set for spatial queries
 * - {prefix}rider:{id}:loc -> STRING with TTL for expiration tracking
 */
class RedisLocationStorage implements LocationStorageInterface
{
    private const string GEO_KEY = 'geo:riders';

    private const string RIDER_LOC_PREFIX = 'rider:';

    private const string RIDER_LOC_SUFFIX = ':loc';

    private readonly string $prefix;

    private readonly int $defaultTtl;

    private readonly int $maxSearchRadius;

    private readonly int $defaultSearchRadius;

    private readonly int $maxNearbyResults;

    public function __construct()
    {
        $this->prefix = config('realtime-cache.redis.prefix', 'rtc:');
        $this->defaultTtl = config('realtime-cache.rider_location.ttl', 30);
        $this->maxSearchRadius = config('realtime-cache.rider_location.max_search_radius', 50000);
        $this->defaultSearchRadius = config('realtime-cache.rider_location.default_search_radius', 5000);
        $this->maxNearbyResults = config('realtime-cache.rider_location.max_nearby_results', 50);
    }

    public function store(int $riderId, Coordinate $coordinate, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $member = $this->getMemberName($riderId);

        $this->redis()->pipeline(function ($pipe) use ($coordinate, $member, $riderId, $ttl) {
            // Store in GEO set for spatial queries
            $pipe->geoadd(
                $this->geoKey(),
                $coordinate->lng(),
                $coordinate->lat(),
                $member
            );

            // Store TTL tracking key with coordinate data
            $pipe->setex(
                $this->riderLocKey($riderId),
                $ttl,
                $coordinate->toString()
            );
        });
    }

    public function get(int $riderId): ?Coordinate
    {
        // Check if TTL key exists (rider is still "active")
        $locData = $this->redis()->get($this->riderLocKey($riderId));

        if ($locData === null) {
            // TTL expired, clean up GEO entry
            $this->remove($riderId);

            return null;
        }

        return Coordinate::fromString($locData);
    }

    public function remove(int $riderId): void
    {
        $member = $this->getMemberName($riderId);

        $this->redis()->pipeline(function ($pipe) use ($member, $riderId) {
            $pipe->zrem($this->geoKey(), $member);
            $pipe->del($this->riderLocKey($riderId));
        });
    }

    public function findNearby(Coordinate $center, Distance $radius, ?int $limit = null): array
    {
        $limit = $limit ?? $this->maxNearbyResults;

        // Clamp radius to max
        $radiusMeters = min($radius->toMeters(), $this->maxSearchRadius);

        // Use GEORADIUS to find nearby riders
        $results = $this->redis()->georadius(
            $this->geoKey(),
            $center->lng(),
            $center->lat(),
            $radiusMeters,
            'm', // meters
            [
                'WITHDIST',
                'WITHCOORD',
                'ASC',
                'COUNT' => $limit * 2, // Get more to filter expired
            ]
        );

        if (empty($results)) {
            return [];
        }

        $nearby = [];

        foreach ($results as $result) {
            $member = $result[0];
            $distance = (float) $result[1];
            $coords = $result[2];

            $riderId = $this->getRiderIdFromMember($member);

            // Check if rider's TTL key still exists
            if (! $this->redis()->exists($this->riderLocKey($riderId))) {
                // Expired, clean up
                $this->redis()->zrem($this->geoKey(), $member);

                continue;
            }

            $nearby[] = [
                'rider_id' => $riderId,
                'distance' => $distance,
                'coordinate' => Coordinate::make((float) $coords[1], (float) $coords[0]),
            ];

            if (count($nearby) >= $limit) {
                break;
            }
        }

        return $nearby;
    }

    public function getAllRiderIds(): array
    {
        $members = $this->redis()->zrange($this->geoKey(), 0, -1);

        if (empty($members)) {
            return [];
        }

        $riderIds = [];

        foreach ($members as $member) {
            $riderId = $this->getRiderIdFromMember($member);

            // Check if TTL key exists
            if ($this->redis()->exists($this->riderLocKey($riderId))) {
                $riderIds[] = $riderId;
            } else {
                // Clean up expired
                $this->redis()->zrem($this->geoKey(), $member);
            }
        }

        return $riderIds;
    }

    public function exists(int $riderId): bool
    {
        return (bool) $this->redis()->exists($this->riderLocKey($riderId));
    }

    public function count(): int
    {
        return count($this->getAllRiderIds());
    }

    public function flush(): void
    {
        // Get all members to delete their TTL keys too
        $members = $this->redis()->zrange($this->geoKey(), 0, -1);

        $this->redis()->pipeline(function ($pipe) use ($members) {
            $pipe->del($this->geoKey());

            foreach ($members as $member) {
                $riderId = $this->getRiderIdFromMember($member);
                $pipe->del($this->riderLocKey($riderId));
            }
        });
    }

    /**
     * Get raw Redis connection
     */
    private function redis(): Connection
    {
        $connection = config('realtime-cache.redis.connection', 'default');

        return Redis::connection($connection);
    }

    private function geoKey(): string
    {
        return $this->prefix . self::GEO_KEY;
    }

    private function riderLocKey(int $riderId): string
    {
        return $this->prefix . self::RIDER_LOC_PREFIX . $riderId . self::RIDER_LOC_SUFFIX;
    }

    private function getMemberName(int $riderId): string
    {
        return 'rider:' . $riderId;
    }

    private function getRiderIdFromMember(string $member): int
    {
        return (int) str_replace('rider:', '', $member);
    }
}
