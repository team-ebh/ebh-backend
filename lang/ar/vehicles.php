<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'المركبات',
        'page_title' => 'المركبات',
        'page_heading' => 'المركبات',

        'sections' => [
            'basic_info' => [
                'title' => 'المعلومات الأساسية',
                'description' => 'تفاصيل المركبة والملكية الأساسية',
            ],
            'vehicle_details' => [
                'title' => 'تفاصيل المركبة',
                'description' => 'المواصفات والخصائص',
            ],
            'accessibility' => [
                'title' => 'ميزات إمكانية الوصول',
                'description' => 'المعدات الخاصة وميزات إمكانية الوصول',
            ],
            'vehicles' => [
                'title' => 'المركبات',
                'description' => 'إدارة مركبات السائق ومواصفاتها',
            ],
            'vehicle_information' => [
                'title' => 'معلومات المركبة',
                'description' => 'مواصفات وتفاصيل المركبة الخاصة بهذا السائق',
            ],
        ],

        'fields' => [
            'rider' => 'السائق',
            'plate_number' => 'رقم اللوحة',
            'year' => 'السنة',
            'car_type' => 'نوع السيارة',
            'car_color' => 'لون السيارة',
            'car_make' => 'ماركة السيارة',
            'car_model' => 'موديل السيارة',
            'passenger_capacity' => 'سعة الركاب',
            'vehicle_type' => 'نوع المركبة',
            'accessibility_features' => 'ميزات إمكانية الوصول',
        ],

        'labels' => [
            'new_vehicle' => 'مركبة جديدة',
        ],

        'actions' => [
            'add_vehicle' => 'إضافة مركبة',
        ],
    ],
];
