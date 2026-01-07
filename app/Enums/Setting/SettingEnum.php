<?php

declare(strict_types=1);

namespace App\Enums\Setting;

enum SettingEnum: string
{
    // Commission
    case DEFAULT_COMMISSION_RATE = 'default_commission_rate';

    // Rider
    case RIDER_TRIP_REQUEST_TIMEOUT_SECONDS = 'rider_trip_request_timeout_seconds';
    case RIDER_LOCATION_UPDATE_INTERVAL_ONLINE = 'rider_location_update_interval_online';
    case RIDER_LOCATION_UPDATE_INTERVAL_BUSY = 'rider_location_update_interval_busy';
    case RIDER_ARRIVING_AT_POLL_INTERVAL = 'rider_arriving_at_poll_interval';

    // Customer
    case CUSTOMER_ARRIVING_AT_POLL_INTERVAL = 'customer_arriving_at_poll_interval';
    case CUSTOMER_MIN_RETURN_TIME_MINUTES = 'customer_min_return_time_minutes';

    // Pricing
    case WAITING_TIME_RATE = 'waiting_time_rate';
    case WAITING_TIME_INTERVAL_MINUTES = 'waiting_time_interval_minutes';

    public function group(): string
    {
        return match ($this) {
            self::DEFAULT_COMMISSION_RATE => 'commission',

            self::RIDER_TRIP_REQUEST_TIMEOUT_SECONDS,
            self::RIDER_LOCATION_UPDATE_INTERVAL_ONLINE,
            self::RIDER_LOCATION_UPDATE_INTERVAL_BUSY,
            self::RIDER_ARRIVING_AT_POLL_INTERVAL => 'rider',

            self::CUSTOMER_ARRIVING_AT_POLL_INTERVAL,
            self::CUSTOMER_MIN_RETURN_TIME_MINUTES => 'customer',

            self::WAITING_TIME_RATE,
            self::WAITING_TIME_INTERVAL_MINUTES => 'pricing',
        };
    }

    public function default(): int | float | string | bool
    {
        return match ($this) {
            self::DEFAULT_COMMISSION_RATE => 15.00,

            self::RIDER_TRIP_REQUEST_TIMEOUT_SECONDS, self::RIDER_LOCATION_UPDATE_INTERVAL_ONLINE => 45,
            self::RIDER_LOCATION_UPDATE_INTERVAL_BUSY => 15,
            self::RIDER_ARRIVING_AT_POLL_INTERVAL, self::CUSTOMER_ARRIVING_AT_POLL_INTERVAL, self::CUSTOMER_MIN_RETURN_TIME_MINUTES => 60,

            self::WAITING_TIME_RATE => 2.500,
            self::WAITING_TIME_INTERVAL_MINUTES => 30,
        };
    }

    public function type(): string
    {
        return match ($this) {
            self::DEFAULT_COMMISSION_RATE,
            self::WAITING_TIME_RATE => 'float',

            self::RIDER_TRIP_REQUEST_TIMEOUT_SECONDS,
            self::RIDER_LOCATION_UPDATE_INTERVAL_ONLINE,
            self::RIDER_LOCATION_UPDATE_INTERVAL_BUSY,
            self::RIDER_ARRIVING_AT_POLL_INTERVAL,
            self::CUSTOMER_ARRIVING_AT_POLL_INTERVAL,
            self::CUSTOMER_MIN_RETURN_TIME_MINUTES,
            self::WAITING_TIME_INTERVAL_MINUTES => 'int',
        };
    }

    public function cast(mixed $value): int | float | string | bool
    {
        return match ($this->type()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            default => (string) $value,
        };
    }
}
