<?php

declare(strict_types=1);

return [
    'admin' => [
        'model_label' => 'Onboarding Page',
        'plural_model_label' => 'Onboarding Pages',
        'navigation_label' => 'Onboarding Pages',
        'application_type' => 'Application Type',
        'application_types' => [
            'customer' => 'Customer App',
            'rider' => 'Rider App',
        ],
        'fields' => [
            'name' => 'Name',
            'application_type' => 'Application Type',
            'enabled' => 'Enabled',
            'banners' => 'Banners',
            'banner_title' => 'Title (English)',
            'banner_title_ar' => 'Title (Arabic)',
            'banner_subtitle' => 'Subtitle (English)',
            'banner_subtitle_ar' => 'Subtitle (Arabic)',
            'banner_image' => 'Banner Image (English)',
            'banner_image_ar' => 'Banner Image (Arabic)',
            'banner_sort' => 'Order',
        ],
        'sections' => [
            'general' => 'General Information',
            'banners' => 'Banners',
        ],
        'hints' => [
            'enabled' => 'Only one onboarding page per application type can be enabled.',
            'banners' => 'Between 2 and 5 banners. Drag to reorder.',
        ],
    ],
];
