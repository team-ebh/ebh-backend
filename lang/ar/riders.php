<?php

declare(strict_types=1);

return [
    'admin' => [
        'fields' => [
            'full_name' => 'الاسم الكامل',
            'email' => 'البريد الإلكتروني',
            'phone_number' => 'رقم الهاتف',
            'company' => 'الشركة',
            'status' => 'الحالة',
        ],
        'statuses' => [
            'online' => 'متصل',
            'offline' => 'غير متصل',
            'busy' => 'مشغول',
        ],
        'model_label' => 'السائق',
        'plural_model_label' => 'السائقون',
        'navigation_label' => 'السائقون',
        'stats' => [
            'total_riders' => 'إجمالي السائقين',
            'total_riders_description' => 'جميع السائقين المسجلين',
            'online_now' => 'متصلون الآن',
            'online_now_description' => 'السائقون المتصلون حالياً',
            'accessibility_certified' => 'معتمدون للوصولية',
            'accessibility_certified_description' => 'السائقون المدربون على إمكانية الوصول',
            'avg_rating' => 'متوسط التقييم',
            'avg_rating_description' => 'متوسط تقييم السائقين',
        ],
        'infolist' => [
            'personal_information' => 'المعلومات الشخصية',
            'status_information' => 'معلومات الحالة',
            'timestamps' => 'طوابع زمنية',
        ],
    ],
    'api' => [

    ],
];
