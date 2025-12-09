<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Settings;

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
     * @return array{arriving_at_poll_interval_seconds: int}
     */
    public function __invoke(): array
    {
        return config('app_settings.customer');
    }
}
