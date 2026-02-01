<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\RealTimeCache\Contracts\LocationStorageInterface;
use App\Services\RealTimeCache\Contracts\LockManagerInterface;
use App\Services\RealTimeCache\Contracts\StatusStorageInterface;
use App\Services\RealTimeCache\Contracts\TripStorageInterface;
use App\Services\RealTimeCache\Drivers\Database\DatabaseLocationStorage;
use App\Services\RealTimeCache\Drivers\Database\DatabaseLockManager;
use App\Services\RealTimeCache\Drivers\Database\DatabaseStatusStorage;
use App\Services\RealTimeCache\Drivers\Database\DatabaseTripStorage;
use App\Services\RealTimeCache\Drivers\Redis\RedisLocationStorage;
use App\Services\RealTimeCache\Drivers\Redis\RedisLockManager;
use App\Services\RealTimeCache\Drivers\Redis\RedisStatusStorage;
use App\Services\RealTimeCache\Drivers\Redis\RedisTripStorage;
use App\Services\RealTimeCache\Fallback\FallbackLocationStorage;
use App\Services\RealTimeCache\Fallback\FallbackLockManager;
use App\Services\RealTimeCache\Fallback\FallbackStatusStorage;
use App\Services\RealTimeCache\Fallback\FallbackTripStorage;
use App\Services\RealTimeCache\Fallback\RedisHealthChecker;
use App\Services\RealTimeCache\RealTimeCacheManager;
use Illuminate\Support\ServiceProvider;

/**
 * RealTime Cache Service Provider
 *
 * Registers real-time cache services with fallback support.
 * When Redis is unavailable, automatically falls back to database.
 *
 * Feature flags allow disabling Redis entirely or specific features:
 * - redis_enabled: Master switch (false = database only)
 * - location_enabled: Location storage feature
 * - status_enabled: Status storage feature
 * - trip_enabled: Trip storage feature
 * - lock_enabled: Lock manager feature
 */
class RealTimeCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/realtime-cache.php'),
            'realtime-cache'
        );

        // Register health checker as singleton
        $this->app->singleton(RedisHealthChecker::class);

        // Register storage interfaces based on feature flags
        $this->registerStorageServices();

        // Register manager as singleton
        $this->app->singleton(RealTimeCacheManager::class, function ($app) {
            return new RealTimeCacheManager(
                $app->make(LocationStorageInterface::class),
                $app->make(StatusStorageInterface::class),
                $app->make(TripStorageInterface::class),
                $app->make(LockManagerInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // Config is already in config/ directory
    }

    /**
     * Register storage services based on feature flags
     */
    private function registerStorageServices(): void
    {
        $enabled = config('realtime-cache.features.enabled', true);

        // Master switch: if cache is disabled, use Database only
        if (! $enabled) {
            $this->registerDatabaseOnly();

            return;
        }

        $fallbackEnabled = config('realtime-cache.fallback.enabled', true);

        // Register each feature based on its individual flag
        $this->registerLocationStorage($fallbackEnabled);
        $this->registerStatusStorage($fallbackEnabled);
        $this->registerTripStorage($fallbackEnabled);
        $this->registerLockManager($fallbackEnabled);
    }

    /**
     * Register Location Storage based on feature flag
     */
    private function registerLocationStorage(bool $fallbackEnabled): void
    {
        $featureEnabled = config('realtime-cache.features.rider_geolocation', true);

        if (! $featureEnabled) {
            $this->app->singleton(LocationStorageInterface::class, DatabaseLocationStorage::class);

            return;
        }

        if ($fallbackEnabled) {
            $this->app->singleton(LocationStorageInterface::class, function ($app) {
                return new FallbackLocationStorage(
                    new RedisLocationStorage(),
                    new DatabaseLocationStorage(),
                    $app->make(RedisHealthChecker::class),
                );
            });
        } else {
            $this->app->singleton(LocationStorageInterface::class, RedisLocationStorage::class);
        }
    }

    /**
     * Register Status Storage based on feature flag
     */
    private function registerStatusStorage(bool $fallbackEnabled): void
    {
        $featureEnabled = config('realtime-cache.features.rider_online_status', true);

        if (! $featureEnabled) {
            $this->app->singleton(StatusStorageInterface::class, DatabaseStatusStorage::class);

            return;
        }

        if ($fallbackEnabled) {
            $this->app->singleton(StatusStorageInterface::class, function ($app) {
                return new FallbackStatusStorage(
                    new RedisStatusStorage(),
                    new DatabaseStatusStorage(),
                    $app->make(RedisHealthChecker::class),
                );
            });
        } else {
            $this->app->singleton(StatusStorageInterface::class, RedisStatusStorage::class);
        }
    }

    /**
     * Register Trip Storage based on feature flag
     */
    private function registerTripStorage(bool $fallbackEnabled): void
    {
        $featureEnabled = config('realtime-cache.features.trip_cache', true);

        if (! $featureEnabled) {
            $this->app->singleton(TripStorageInterface::class, DatabaseTripStorage::class);

            return;
        }

        if ($fallbackEnabled) {
            $this->app->singleton(TripStorageInterface::class, function ($app) {
                return new FallbackTripStorage(
                    new RedisTripStorage(),
                    new DatabaseTripStorage(),
                    $app->make(RedisHealthChecker::class),
                );
            });
        } else {
            $this->app->singleton(TripStorageInterface::class, RedisTripStorage::class);
        }
    }

    /**
     * Register Lock Manager based on feature flag
     */
    private function registerLockManager(bool $fallbackEnabled): void
    {
        $featureEnabled = config('realtime-cache.features.assignment_lock', true);

        if (! $featureEnabled) {
            $this->app->singleton(LockManagerInterface::class, DatabaseLockManager::class);

            return;
        }

        if ($fallbackEnabled) {
            $this->app->singleton(LockManagerInterface::class, function ($app) {
                return new FallbackLockManager(
                    new RedisLockManager(),
                    new DatabaseLockManager(),
                    $app->make(RedisHealthChecker::class),
                );
            });
        } else {
            $this->app->singleton(LockManagerInterface::class, RedisLockManager::class);
        }
    }

    /**
     * Register Database-only implementations (Redis disabled)
     */
    private function registerDatabaseOnly(): void
    {
        $this->app->singleton(LocationStorageInterface::class, DatabaseLocationStorage::class);
        $this->app->singleton(StatusStorageInterface::class, DatabaseStatusStorage::class);
        $this->app->singleton(TripStorageInterface::class, DatabaseTripStorage::class);
        $this->app->singleton(LockManagerInterface::class, DatabaseLockManager::class);
    }
}
