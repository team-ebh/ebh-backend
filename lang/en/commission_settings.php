<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'Commissions',
        'page_title' => 'Commissions',
        'page_heading' => 'Commissions',

        'stats' => [
            'total_commission' => 'Total Commission',
            'companies_custom_rates' => 'Companies w/ Custom Rates',
            'companies' => 'companies',
            'avg_rate' => 'Avg Commission Rate',
            'average' => 'average',
            'this_month' => 'This Month',
        ],

        'sections' => [
            'default_rate' => [
                'title' => 'Default Commission Rate',
                'description' => 'This rate will be applied to all companies using the default rate',
            ],
            'company_rates' => [
                'title' => 'Company Commission Rates',
                'description' => 'All companies with their commission rates. Edit to set custom or default rate.',
            ],
        ],

        'fields' => [
            'default_rate' => 'Default Rate',
            'default_rate_helper' => 'The default commission rate applied to companies without a custom rate',
            'commission_rate' => 'Commission Rate',
            'company' => 'Company',
            'rate_type' => 'Rate Type',
            'rate_type_default' => 'Default Rate (:rate)',
            'rate_type_custom' => 'Custom Rate',
        ],

        'table' => [
            'company_name' => 'Company',
            'rate' => 'Rate',
            'rate_type' => 'Type',
            'custom' => 'Custom',
            'default' => 'Default',
            'trips' => 'Trips',
            'trips_label' => 'trips',
            'revenue' => 'Revenue',
            'commission' => 'Commission',
            'empty_heading' => 'No companies',
            'empty_description' => 'No companies have been created yet.',
        ],

        'actions' => [
            'save_default_rate' => 'Save Default Rate',
            'edit_rate' => 'Edit',
        ],

        'notifications' => [
            'default_rate_saved' => 'Default commission rate saved and applied to all companies using default rate',
            'rate_updated' => 'Commission rate updated successfully',
            'error' => 'An error occurred',
        ],
    ],
];
