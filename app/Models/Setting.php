<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasDefaultColumnModelTrait;
    use LogsActivity;

    public const string COLUMN_KEY = 'key';

    public const string COLUMN_VALUE = 'value';

    public const string COLUMN_GROUP = 'group';

    /**
     * Setting Keys
     */
    public const string KEY_DEFAULT_COMMISSION_RATE = 'default_commission_rate';

    /**
     * Setting Groups
     */
    public const string GROUP_COMMISSION = 'commission';

    /**
     * Cache Keys
     */
    private const string CACHE_PREFIX = 'settings:';

    private const int CACHE_TTL = 3600; // 1 hour

    protected $casts = [
        self::COLUMN_VALUE => 'string',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::query()->where(self::COLUMN_KEY, $key)->first();

            return $setting ? $setting->{self::COLUMN_VALUE} : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, string $group = 'general'): Model
    {
        $setting = self::query()->updateOrCreate(
            [self::COLUMN_KEY => $key],
            [
                self::COLUMN_VALUE => $value,
                self::COLUMN_GROUP => $group,
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * Get default commission rate
     */
    public static function getDefaultCommissionRate(): float
    {
        return (float) self::getValue(self::KEY_DEFAULT_COMMISSION_RATE, 15.00);
    }

    /**
     * Set default commission rate
     */
    public static function setDefaultCommissionRate(float $rate): Model
    {
        return self::setValue(self::KEY_DEFAULT_COMMISSION_RATE, $rate, self::GROUP_COMMISSION);
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return self::query()
            ->where(self::COLUMN_GROUP, $group)
            ->pluck(self::COLUMN_VALUE, self::COLUMN_KEY)
            ->toArray();
    }
}
