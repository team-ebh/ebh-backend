<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Database;

use App\Models\Rider;
use App\Services\RealTimeCache\Contracts\LocationStorageInterface;
use App\Services\RealTimeCache\ValueObjects\Coordinate;
use App\Services\RealTimeCache\ValueObjects\Distance;
use Illuminate\Support\Facades\DB;

/**
 * Database Location Storage (Fallback)
 *
 * Uses the riders table for location storage when Redis is unavailable.
 * Less performant than Redis but ensures system availability.
 */
class DatabaseLocationStorage implements LocationStorageInterface
{
    private readonly int $defaultTtl;

    private readonly int $maxSearchRadius;

    private readonly int $maxNearbyResults;

    public function __construct()
    {
        $this->defaultTtl = config('realtime-cache.rider_location.ttl', 30);
        $this->maxSearchRadius = config('realtime-cache.rider_location.max_search_radius', 50000);
        $this->maxNearbyResults = config('realtime-cache.rider_location.max_nearby_results', 50);
    }

    public function store(int $riderId, Coordinate $coordinate, ?int $ttl = null): void
    {
        Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->update([
                Rider::COLUMN_LATITUDE => $coordinate->lat(),
                Rider::COLUMN_LONGITUDE => $coordinate->lng(),
                Rider::COLUMN_LAST_LOCATION_UPDATE => now(),
            ]);
    }

    public function get(int $riderId): ?Coordinate
    {
        $rider = Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->whereNotNull(Rider::COLUMN_LATITUDE)
            ->whereNotNull(Rider::COLUMN_LONGITUDE)
            ->where(Rider::COLUMN_LAST_LOCATION_UPDATE, '>=', now()->subSeconds($this->defaultTtl))
            ->first([Rider::COLUMN_LATITUDE, Rider::COLUMN_LONGITUDE]);

        if (! $rider) {
            return null;
        }

        return Coordinate::make(
            (float) $rider->{Rider::COLUMN_LATITUDE},
            (float) $rider->{Rider::COLUMN_LONGITUDE}
        );
    }

    public function remove(int $riderId): void
    {
        Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->update([
                Rider::COLUMN_LATITUDE => null,
                Rider::COLUMN_LONGITUDE => null,
                Rider::COLUMN_LAST_LOCATION_UPDATE => null,
            ]);
    }

    public function findNearby(Coordinate $center, Distance $radius, ?int $limit = null): array
    {
        $limit = $limit ?? $this->maxNearbyResults;
        $radiusMeters = min($radius->toMeters(), $this->maxSearchRadius);
        $radiusKm = $radiusMeters / 1000;

        // Haversine formula in SQL
        $haversine = '(
            6371 * acos(
                cos(radians(?)) *
                cos(radians(' . Rider::COLUMN_LATITUDE . ')) *
                cos(radians(' . Rider::COLUMN_LONGITUDE . ') - radians(?)) +
                sin(radians(?)) *
                sin(radians(' . Rider::COLUMN_LATITUDE . '))
            )
        )';

        $riders = Rider::query()
            ->select([
                Rider::COLUMN_ID,
                Rider::COLUMN_LATITUDE,
                Rider::COLUMN_LONGITUDE,
                DB::raw("{$haversine} AS distance"),
            ])
            ->whereNotNull(Rider::COLUMN_LATITUDE)
            ->whereNotNull(Rider::COLUMN_LONGITUDE)
            ->where(Rider::COLUMN_LAST_LOCATION_UPDATE, '>=', now()->subSeconds($this->defaultTtl))
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->setBindings([$center->lat(), $center->lng(), $center->lat()])
            ->get();

        $result = [];

        foreach ($riders as $rider) {
            $result[] = [
                'rider_id' => $rider->{Rider::COLUMN_ID},
                'distance' => $rider->distance * 1000, // Convert km to meters
                'coordinate' => Coordinate::make(
                    (float) $rider->{Rider::COLUMN_LATITUDE},
                    (float) $rider->{Rider::COLUMN_LONGITUDE}
                ),
            ];
        }

        return $result;
    }

    public function getAllRiderIds(): array
    {
        return Rider::query()
            ->whereNotNull(Rider::COLUMN_LATITUDE)
            ->whereNotNull(Rider::COLUMN_LONGITUDE)
            ->where(Rider::COLUMN_LAST_LOCATION_UPDATE, '>=', now()->subSeconds($this->defaultTtl))
            ->pluck(Rider::COLUMN_ID)
            ->toArray();
    }

    public function exists(int $riderId): bool
    {
        return Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->whereNotNull(Rider::COLUMN_LATITUDE)
            ->whereNotNull(Rider::COLUMN_LONGITUDE)
            ->where(Rider::COLUMN_LAST_LOCATION_UPDATE, '>=', now()->subSeconds($this->defaultTtl))
            ->exists();
    }

    public function count(): int
    {
        return Rider::query()
            ->whereNotNull(Rider::COLUMN_LATITUDE)
            ->whereNotNull(Rider::COLUMN_LONGITUDE)
            ->where(Rider::COLUMN_LAST_LOCATION_UPDATE, '>=', now()->subSeconds($this->defaultTtl))
            ->count();
    }

    public function flush(): void
    {
        Rider::query()->update([
            Rider::COLUMN_LATITUDE => null,
            Rider::COLUMN_LONGITUDE => null,
            Rider::COLUMN_LAST_LOCATION_UPDATE => null,
        ]);
    }
}
