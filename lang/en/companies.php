<?php

declare(strict_types=1);

return [
    'admin' => [
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'address' => 'Address',
            'commission_rate' => 'Commission Rate',
            'riders_count' => 'Riders',
            'enabled' => 'Is Enabled?',
        ],
        'model_label' => 'Company',
        'plural_model_label' => 'Companies',
        'navigation_label' => 'Companies',
        'stats' => [
            'total_companies' => 'Total Companies',
            'total_companies_description' => 'All registered companies',
            'active_companies' => 'Active Companies',
            'active_companies_description' => 'Currently active companies',
            'total_riders' => 'Total Riders',
            'total_riders_description' => 'All riders across companies',
            'total_earnings' => 'Total Earnings',
            'total_earnings_description' => 'Total revenue from trips',
        ],
    ],
    'api' => [

    ],
];
