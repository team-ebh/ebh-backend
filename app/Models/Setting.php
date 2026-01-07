<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Setting\SettingEnum;
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
     * Cache configuration
     */
    private const string CACHE_PREFIX = 'settings:';

    private const int CACHE_TTL = 3600; // 1 hour

    protected $casts = [
        self::COLUMN_VALUE => 'string',
    ];

    /**
     * Get a setting value by enum
     */
    public static function get(SettingEnum $setting): int | float | string | bool
    {
        $cacheKey = self::CACHE_PREFIX . $setting->value;

        $value = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($setting) {
            return self::query()->where(self::COLUMN_KEY, $setting->value)->first()?->{self::COLUMN_VALUE};
        });

        if ($value === null) {
            return $setting->default();
        }

        return $setting->cast($value);
    }

    /**
     * Set a setting value by enum
     */
    public static function set(SettingEnum $setting, int | float | string | bool $value): Model
    {
        $record = self::query()->updateOrCreate(
            [self::COLUMN_KEY => $setting->value],
            [
                self::COLUMN_VALUE => $value,
                self::COLUMN_GROUP => $setting->group(),
            ]
        );

        Cache::forget(self::CACHE_PREFIX . $setting->value);

        return $record;
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
