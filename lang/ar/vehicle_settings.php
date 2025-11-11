<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'إعدادات المركبات',
        'page_title' => 'إعدادات المركبات',
        'page_heading' => 'إعدادات المركبات',

        'sections' => [
            'car_types' => [
                'title' => 'أنواع السيارات',
                'description' => 'إدارة أنواع السيارات المتاحة (مثل السيدان، الدفع الرباعي، الهاتشباك)',
            ],
            'car_colors' => [
                'title' => 'ألوان السيارات',
                'description' => 'إدارة ألوان السيارات المتاحة (مثل الأبيض، الأسود، الفضي)',
            ],
            'passenger_capacity' => [
                'title' => 'سعة الركاب',
                'description' => 'تحديد خيارات سعة الركاب (من 1 إلى 6 راكب)',
            ],
            'accessibility_features' => [
                'title' => 'ميزات إمكانية الوصول',
                'description' => 'إدارة ميزات إمكانية الوصول (مثل الكرسي المتحرك، دعم الأكسجين)',
            ],
            'car_makes' => [
                'title' => 'ماركات السيارات',
                'description' => 'إدارة الشركات المصنعة للسيارات (مثل تويوتا، هوندا، فورد)',
            ],
            'car_models' => [
                'title' => 'موديلات السيارات',
                'description' => 'إدارة موديلات السيارات (مثل كامري، أكورد، موستانج)',
            ],
            'vehicle_types' => [
                'title' => 'أنواع المركبات',
                'description' => 'إدارة أنواع المركبات (مثل سرير/نقالة، معدات الأكسجين، مساعدات التنقل)',
            ],
        ],

        'fields' => [
            'name' => 'الاسم (بالإنجليزية)',
            'name_ar' => 'الاسم (بالعربية)',
            'capacity' => 'السعة',
        ],

        'labels' => [
            'passengers' => 'راكب',
        ],

        'actions' => [
            'add_car_type' => 'إضافة نوع سيارة',
            'add_car_color' => 'إضافة لون سيارة',
            'add_capacity' => 'إضافة سعة',
            'add_accessibility_feature' => 'إضافة ميزة إمكانية وصول',
            'add_car_make' => 'إضافة ماركة سيارة',
            'add_car_model' => 'إضافة موديل سيارة',
            'add_vehicle_type' => 'إضافة نوع مركبة',
            'save_all_changes' => 'حفظ جميع التغييرات',
        ],

        'notifications' => [
            'saved' => 'تم حفظ إعدادات المركبات بنجاح.',
        ],
    ],
];
