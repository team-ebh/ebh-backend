<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Models\Rider;
use App\Services\RealTimeCache\RealTimeCacheManager;
use App\Services\RealTimeCache\ValueObjects\Coordinate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Rider Cache Service
 *
 * Manages caching of rider data including locations, statuses, and metadata
 */
class RiderCacheService
{
    public const string CACHE_PREFIX = 'rider:';

    public const string CACHE_ALL_RIDERS_KEY = 'riders:all';

    public const int CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly RealTimeCacheManager $rtCache
    ) {}

    /**
     * Update cache for a single rider
     */
    public function updateRider(int $riderId): bool
    {
        try {
            $rider = Rider::query()
                ->where(Rider::COLUMN_ID, $riderId)
                ->with(['vehicle', 'company'])
                ->first();

            if (! $rider) {
                $this->removeRider($riderId);

                return false;
            }

            // Cache rider data
            Cache::put(
                self::CACHE_PREFIX . $riderId,
                $this->transformRiderData($rider),
                self::CACHE_TTL
            );

            // Update location in RealTimeCache if available
            if ($rider->{Rider::COLUMN_LATITUDE} && $rider->{Rider::COLUMN_LONGITUDE}) {
                $this->rtCache->location()->store(
                    $riderId,
                    Coordinate::make(
                        (float) $rider->{Rider::COLUMN_LATITUDE},
                        (float) $rider->{Rider::COLUMN_LONGITUDE}
                    )
                );
            }

            // Update status in RealTimeCache
            $this->updateRiderStatus($rider);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to update rider cache', [
                'rider_id' => $riderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update cache for all riders
     */
    public function updateAllRiders(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        $riders = Rider::query()
            ->with(['vehicle', 'company'])
            ->get();

        $stats['total'] = $riders->count();

        foreach ($riders as $rider) {
            if ($this->updateRider($rider->{Rider::COLUMN_ID})) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        // Update all riders list cache
        Cache::put(
            self::CACHE_ALL_RIDERS_KEY,
            $riders->pluck(Rider::COLUMN_ID)->toArray(),
            self::CACHE_TTL
        );

        return $stats;
    }

    /**
     * Update only online riders
     */
    public function updateOnlineRiders(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        $riders = Rider::query()
            ->where(Rider::COLUMN_STATUS, '!=', 'offline')
            ->with(['vehicle', 'company'])
            ->get();

        $stats['total'] = $riders->count();

        foreach ($riders as $rider) {
            if ($this->updateRider($rider->{Rider::COLUMN_ID})) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Remove rider from cache
     */
    public function removeRider(int $riderId): void
    {
        Cache::forget(self::CACHE_PREFIX . $riderId);
        $this->rtCache->location()->forget($riderId);
        $this->rtCache->status()->forget($riderId);
    }

    /**
     * Get rider from cache
     */
    public function getRider(int $riderId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $riderId);
    }

    /**
     * Flush all rider caches
     */
    public function flush(): void
    {
        $riderIds = Cache::get(self::CACHE_ALL_RIDERS_KEY, []);

        foreach ($riderIds as $riderId) {
            $this->removeRider($riderId);
        }

        Cache::forget(self::CACHE_ALL_RIDERS_KEY);
        $this->rtCache->location()->flush();
        $this->rtCache->status()->flush();
    }

    /**
     * Transform rider data for caching
     */
    private function transformRiderData(Rider $rider): array
    {
        return [
            'id' => $rider->{Rider::COLUMN_ID},
            'full_name' => $rider->{Rider::COLUMN_FULL_NAME},
            'phone_number' => $rider->{Rider::COLUMN_PHONE_NUMBER},
            'email' => $rider->{Rider::COLUMN_EMAIL},
            'status' => $rider->{Rider::COLUMN_STATUS}->value,
            'latitude' => $rider->{Rider::COLUMN_LATITUDE},
            'longitude' => $rider->{Rider::COLUMN_LONGITUDE},
            'last_location_update' => $rider->{Rider::COLUMN_LAST_LOCATION_UPDATE},
            'company_id' => $rider->{Rider::COLUMN_COMPANY_ID},
            'vehicle' => $rider->vehicle ? [
                'id' => $rider->vehicle->id,
                'plate_number' => $rider->vehicle->plate_number,
                'car_type_id' => $rider->vehicle->car_type_id,
            ] : null,
            'cached_at' => now()->timestamp,
        ];
    }

    /**
     * Update rider status in RealTimeCache
     */
    private function updateRiderStatus(Rider $rider): void
    {
        $meta = [
            'vehicle_id' => $rider->vehicle?->id,
            'last_update' => $rider->{Rider::COLUMN_LAST_LOCATION_UPDATE},
        ];

        if ($rider->isOnline()) {
            $this->rtCache->status()->setOnline($rider->{Rider::COLUMN_ID}, $meta);
        } elseif ($rider->isBusy()) {
            $this->rtCache->status()->setBusy($rider->{Rider::COLUMN_ID}, $meta);
        } else {
            $this->rtCache->status()->forget($rider->{Rider::COLUMN_ID});
        }
    }
}
