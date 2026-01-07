<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Settings;

use App\Enums\Setting\SettingEnum;
use App\Models\Setting;

/**
 * Get Customer Settings Action
 *
 * Returns customer-related settings and configurations
 */
readonly class GetCustomerSettingsAction
{
    /**
     * Execute the action
     *
     * @return array{arriving_at_poll_interval_seconds: int, min_return_time_minutes: int}
     */
    public function __invoke(): array
    {
        return [
            'arriving_at_poll_interval_seconds' => Setting::get(SettingEnum::CUSTOMER_ARRIVING_AT_POLL_INTERVAL),
            'min_return_time_minutes' => Setting::get(SettingEnum::CUSTOMER_MIN_RETURN_TIME_MINUTES),
        ];
    }
}
