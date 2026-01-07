<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'إعدادات التطبيق',
        'page_title' => 'إعدادات التطبيق',
        'page_heading' => 'إعدادات التطبيق',

        'tabs' => [
            'rider' => 'إعدادات السائق',
            'customer' => 'إعدادات العميل',
            'pricing' => 'إعدادات التسعير',
        ],

        'sections' => [
            'rider' => [
                'title' => 'إعدادات تطبيق السائق',
                'description' => 'تكوين إعدادات تطبيق السائق المحمول',
            ],
            'customer' => [
                'title' => 'إعدادات تطبيق العميل',
                'description' => 'تكوين إعدادات تطبيق العميل المحمول',
            ],
            'pricing' => [
                'title' => 'تسعير وقت الانتظار',
                'description' => 'تكوين تسعير وقت الانتظار لرحلات الذهاب والعودة مع الانتظار',
            ],
        ],

        'fields' => [
            'rider_trip_request_timeout_seconds' => 'مهلة طلب الرحلة',
            'rider_location_update_interval_online' => 'فترة تحديث الموقع (متصل)',
            'rider_location_update_interval_busy' => 'فترة تحديث الموقع (مشغول)',
            'rider_arriving_at_poll_interval' => 'فترة استعلام وقت الوصول',
            'customer_arriving_at_poll_interval' => 'فترة استعلام وقت الوصول',
            'customer_min_return_time_minutes' => 'الحد الأدنى لوقت العودة',
            'waiting_time_rate' => 'سعر وقت الانتظار',
            'waiting_time_interval_minutes' => 'فترة وقت الانتظار',
        ],

        'helpers' => [
            'rider_trip_request_timeout_seconds' => 'مدة صلاحية طلب الرحلة قبل انتهاء الصلاحية (10-300 ثانية)',
            'rider_location_update_interval_online' => 'عدد مرات إرسال تحديثات الموقع للسائقين المتصلين (10-300 ثانية)',
            'rider_location_update_interval_busy' => 'عدد مرات إرسال تحديثات الموقع للسائقين المشغولين (5-120 ثانية)',
            'rider_arriving_at_poll_interval' => 'عدد مرات الاستعلام عن تحديثات وقت الوصول (10-300 ثانية)',
            'customer_arriving_at_poll_interval' => 'عدد مرات الاستعلام عن تحديثات وقت الوصول (10-300 ثانية)',
            'customer_min_return_time_minutes' => 'الحد الأدنى للوقت قبل أن يتمكن العميل من جدولة العودة (15-480 دقيقة)',
            'waiting_time_rate' => 'السعر لكل فترة انتظار (0.100-50.000 دينار كويتي)',
            'waiting_time_interval_minutes' => 'مدة كل فترة انتظار (5-120 دقيقة)',
        ],

        'units' => [
            'seconds' => 'ثانية',
            'minutes' => 'دقيقة',
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
