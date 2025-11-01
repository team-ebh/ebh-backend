<?php

declare(strict_types=1);

return [
    'admin' => [
        'fields' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone_number' => 'رقم الهاتف',
            'address' => 'العنوان',
            'commission_rate' => 'نسبة العمولة',
            'riders_count' => 'السائقين',
            'enabled' => 'مفعل؟',
        ],
        'model_label' => 'الشركة',
        'plural_model_label' => 'الشركات',
        'navigation_label' => 'الشركات',
        'stats' => [
            'total_companies' => 'إجمالي الشركات',
            'total_companies_description' => 'جميع الشركات المسجلة',
            'active_companies' => 'الشركات النشطة',
            'active_companies_description' => 'الشركات النشطة حالياً',
            'total_riders' => 'إجمالي السائقين',
            'total_riders_description' => 'جميع السائقين في جميع الشركات',
            'total_earnings' => 'إجمالي الأرباح',
            'total_earnings_description' => 'إجمالي الإيرادات من الرحلات',
        ],
        'infolist' => [
            'basic_information' => 'المعلومات الأساسية',
            'business_information' => 'معلومات العمل',
            'timestamps' => 'طوابع زمنية',
        ],
    ],
    'api' => [

    ],
];
