<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Settings;

use App\Enums\Setting\SettingEnum;
use App\Models\Setting;

/**
 * Get Rider Settings Action
 *
 * Returns rider-related settings and configurations
 */
readonly class GetRiderSettingsAction
{
    /**
     * Execute the action
     *
     * @return array{trip_request_timeout_seconds: int, rider_location_update_interval_seconds_online: int, rider_location_update_interval_seconds_busy: int, arriving_at_poll_interval_seconds: int}
     */
    public function __invoke(): array
    {
        return [
            'trip_request_timeout_seconds' => Setting::get(SettingEnum::RIDER_TRIP_REQUEST_TIMEOUT_SECONDS),
            'rider_location_update_interval_seconds_online' => Setting::get(SettingEnum::RIDER_LOCATION_UPDATE_INTERVAL_ONLINE),
            'rider_location_update_interval_seconds_busy' => Setting::get(SettingEnum::RIDER_LOCATION_UPDATE_INTERVAL_BUSY),
            'arriving_at_poll_interval_seconds' => Setting::get(SettingEnum::RIDER_ARRIVING_AT_POLL_INTERVAL),
        ];
    }
}
