<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'الإعدادات التقنية',
        'page_title' => 'الإعدادات التقنية',
        'page_heading' => 'الإعدادات التقنية',

        'tabs' => [
            'rider' => 'إعدادات السائق',
            'customer' => 'إعدادات العميل',
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
        ],

        'fields' => [
            'rider_trip_request_timeout_seconds' => 'مهلة طلب الرحلة',
            'rider_location_update_interval_online' => 'فترة تحديث الموقع (متصل)',
            'rider_location_update_interval_busy' => 'فترة تحديث الموقع (مشغول)',
            'rider_arriving_at_poll_interval' => 'فترة استعلام وقت الوصول',
            'customer_arriving_at_poll_interval' => 'فترة استعلام وقت الوصول',
            'customer_min_return_time_minutes' => 'الحد الأدنى لوقت العودة',
        ],

        'helpers' => [
            'rider_trip_request_timeout_seconds' => 'مدة صلاحية طلب الرحلة قبل انتهاء الصلاحية (10-300 ثانية)',
            'rider_location_update_interval_online' => 'عدد مرات إرسال تحديثات الموقع للسائقين المتصلين (10-300 ثانية)',
            'rider_location_update_interval_busy' => 'عدد مرات إرسال تحديثات الموقع للسائقين المشغولين (5-120 ثانية)',
            'rider_arriving_at_poll_interval' => 'عدد مرات الاستعلام عن تحديثات وقت الوصول (10-300 ثانية)',
            'customer_arriving_at_poll_interval' => 'عدد مرات الاستعلام عن تحديثات وقت الوصول (10-300 ثانية)',
            'customer_min_return_time_minutes' => 'الحد الأدنى للوقت قبل أن يتمكن العميل من جدولة العودة (15-480 دقيقة)',
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
