<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'إعدادات التطبيق',
        'page_title' => 'إعدادات التطبيق',
        'page_heading' => 'إعدادات التطبيق',

        'tabs' => [
            'pricing' => 'إعدادات التسعير',
            'scheduling' => 'إعدادات الجدولة',
        ],

        'sections' => [
            'pricing' => [
                'title' => 'تسعير وقت الانتظار',
                'description' => 'تكوين تسعير وقت الانتظار لرحلات الذهاب والعودة مع الانتظار',
            ],
            'scheduling' => [
                'title' => 'جدولة الرحلات',
                'description' => 'تكوين إعدادات الرحلات المجدولة',
            ],
        ],

        'fields' => [
            'waiting_time_rate' => 'سعر وقت الانتظار',
            'waiting_time_interval_minutes' => 'فترة وقت الانتظار',
            'scheduled_trip_search_start_minutes' => 'وقت بدء البحث',
            'customer_min_return_time_minutes' => 'الحد الأدنى لوقت العودة',
            'customer_min_schedule_time_minutes' => 'الحد الأدنى لوقت الجدولة',
            'customer_max_schedule_time_days' => 'الحد الأقصى لوقت الجدولة',
        ],

        'helpers' => [
            'waiting_time_rate' => 'السعر لكل فترة انتظار (0.100-50.000 دينار كويتي)',
            'waiting_time_interval_minutes' => 'مدة كل فترة انتظار (5-120 دقيقة)',
            'scheduled_trip_search_start_minutes' => 'كم دقيقة قبل الموعد المحدد لبدء البحث عن سائق (1-60 دقيقة)',
            'customer_min_return_time_minutes' => 'الحد الأدنى للوقت قبل أن يتمكن العميل من جدولة العودة (15-480 دقيقة)',
            'customer_min_schedule_time_minutes' => 'الحد الأدنى للوقت المسبق الذي يمكن للعميل جدولة رحلة فيه (5-1440 دقيقة)',
            'customer_max_schedule_time_days' => 'الحد الأقصى للوقت المسبق الذي يمكن للعميل جدولة رحلة فيه (1-365 يوم). اتركه فارغًا لعدم وجود حد.',
        ],

        'placeholders' => [
            'no_limit' => 'بدون حد',
        ],

        'units' => [
            'seconds' => 'ثانية',
            'minutes' => 'دقيقة',
            'days' => 'يوم',
        ],

        'actions' => [
            'save' => 'حفظ الإعدادات',
        ],

        'notifications' => [
            'saved' => 'تم حفظ الإعدادات بنجاح',
            'error' => 'خطأ في حفظ الإعدادات',
        ],
    ],
];
