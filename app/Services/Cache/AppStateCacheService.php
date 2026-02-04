<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Models\Setting;
use App\Models\VehicleSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * App State Cache Service
 *
 * Manages caching of application state including settings and vehicle settings
 */
class AppStateCacheService
{
    public const string CACHE_ALL_SETTINGS_KEY = 'app_state:settings:all';

    public const string CACHE_VEHICLE_SETTINGS_PREFIX = 'app_state:vehicle_settings:';

    public const string CACHE_ALL_VEHICLE_SETTINGS_KEY = 'app_state:vehicle_settings:all';

    public const int CACHE_TTL = 86400; // 24 hours (app state changes rarely)

    /**
     * Update cache for all settings
     */
    public function updateSettings(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        try {
            // Get all settings from database
            $settings = Setting::all();
            $stats['total'] = $settings->count();

            // Cache all settings as a collection
            $settingsData = $settings->map(function ($setting) {
                return [
                    'key' => $setting->{Setting::COLUMN_KEY},
                    'value' => $setting->{Setting::COLUMN_VALUE},
                    'group' => $setting->{Setting::COLUMN_GROUP},
                ];
            })->keyBy('key')->toArray();

            Cache::put(
                self::CACHE_ALL_SETTINGS_KEY,
                $settingsData,
                self::CACHE_TTL
            );

            // Also cache individual settings for the Setting model's get() method
            foreach ($settings as $setting) {
                $cacheKey = 'settings:' . $setting->{Setting::COLUMN_KEY};
                Cache::put($cacheKey, $setting->{Setting::COLUMN_VALUE}, 3600);
            }

            $stats['success'] = $stats['total'];

            return $stats;
        } catch (\Throwable $e) {
            Log::error('Failed to update settings cache', [
                'error' => $e->getMessage(),
            ]);

            $stats['failed'] = $stats['total'];

            return $stats;
        }
    }

    /**
     * Update cache for all vehicle settings
     */
    public function updateVehicleSettings(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        try {
            // Define vehicle setting types
            $types = [
                VehicleSetting::TYPE_CAR_TYPES,
                VehicleSetting::TYPE_CAR_COLORS,
                VehicleSetting::TYPE_PASSENGER_CAPACITY,
                VehicleSetting::TYPE_CAR_MAKES,
                VehicleSetting::TYPE_CAR_MODELS,
            ];

            $allVehicleSettings = [];

            foreach ($types as $type) {
                $settings = VehicleSetting::getByType($type);
                $stats['total'] += $settings->count();

                $settingsData = $settings->map(function ($setting) use ($type) {
                    return [
                        'id' => $setting->{VehicleSetting::COLUMN_ID},
                        'type' => $type,
                        'name' => $setting->{VehicleSetting::COLUMN_NAME},
                        'name_ar' => $setting->{VehicleSetting::COLUMN_NAME_AR},
                        'capacity' => $setting->{VehicleSetting::COLUMN_CAPACITY},
                        'order' => $setting->{VehicleSetting::COLUMN_ORDER},
                    ];
                })->toArray();

                // Cache by type
                Cache::put(
                    self::CACHE_VEHICLE_SETTINGS_PREFIX . $type,
                    $settingsData,
                    self::CACHE_TTL
                );

                $allVehicleSettings[$type] = $settingsData;
            }

            // Cache all vehicle settings together
            Cache::put(
                self::CACHE_ALL_VEHICLE_SETTINGS_KEY,
                $allVehicleSettings,
                self::CACHE_TTL
            );

            $stats['success'] = $stats['total'];

            return $stats;
        } catch (\Throwable $e) {
            Log::error('Failed to update vehicle settings cache', [
                'error' => $e->getMessage(),
            ]);

            $stats['failed'] = $stats['total'];

            return $stats;
        }
    }

    /**
     * Update all app state caches
     */
    public function updateAll(): array
    {
        $settingsStats = $this->updateSettings();
        $vehicleSettingsStats = $this->updateVehicleSettings();

        return [
            'settings' => $settingsStats,
            'vehicle_settings' => $vehicleSettingsStats,
            'total' => $settingsStats['total'] + $vehicleSettingsStats['total'],
            'success' => $settingsStats['success'] + $vehicleSettingsStats['success'],
            'failed' => $settingsStats['failed'] + $vehicleSettingsStats['failed'],
        ];
    }

    /**
     * Get all settings from cache
     */
    public function getSettings(): ?array
    {
        return Cache::get(self::CACHE_ALL_SETTINGS_KEY);
    }

    /**
     * Get vehicle settings by type from cache
     */
    public function getVehicleSettingsByType(string $type): ?array
    {
        return Cache::get(self::CACHE_VEHICLE_SETTINGS_PREFIX . $type);
    }

    /**
     * Get all vehicle settings from cache
     */
    public function getAllVehicleSettings(): ?array
    {
        return Cache::get(self::CACHE_ALL_VEHICLE_SETTINGS_KEY);
    }

    /**
     * Flush all app state caches
     */
    public function flush(): void
    {
        // Flush main caches
        Cache::forget(self::CACHE_ALL_SETTINGS_KEY);
        Cache::forget(self::CACHE_ALL_VEHICLE_SETTINGS_KEY);

        // Flush vehicle settings by type
        $types = [
            VehicleSetting::TYPE_CAR_TYPES,
            VehicleSetting::TYPE_CAR_COLORS,
            VehicleSetting::TYPE_PASSENGER_CAPACITY,
            VehicleSetting::TYPE_CAR_MAKES,
            VehicleSetting::TYPE_CAR_MODELS,
        ];

        foreach ($types as $type) {
            Cache::forget(self::CACHE_VEHICLE_SETTINGS_PREFIX . $type);
        }

        // Flush individual setting caches (for Setting model's get() method)
        $settings = Setting::all();
        foreach ($settings as $setting) {
            Cache::forget('settings:' . $setting->{Setting::COLUMN_KEY});
        }
    }

    /**
     * Get app state statistics
     */
    public function getStats(): array
    {
        $settings = $this->getSettings();
        $vehicleSettings = $this->getAllVehicleSettings();

        return [
            'settings_count' => $settings ? count($settings) : 0,
            'vehicle_settings_count' => $vehicleSettings ? collect($vehicleSettings)->flatten(1)->count() : 0,
            'settings_cached' => $settings !== null,
            'vehicle_settings_cached' => $vehicleSettings !== null,
        ];
    }
}
