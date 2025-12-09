<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Settings;

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
            'trip_request_timeout_seconds' => 45,
            'rider_location_update_interval_seconds_online' => 15,
            'rider_location_update_interval_seconds_busy' => 30,
            'arriving_at_poll_interval_seconds' => 60,
        ];
    }
}
