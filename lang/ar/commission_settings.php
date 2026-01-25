<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'العمولات',
        'page_title' => 'العمولات',
        'page_heading' => 'العمولات',

        'stats' => [
            'total_commission' => 'إجمالي العمولة',
            'companies_custom_rates' => 'شركات بأسعار مخصصة',
            'companies' => 'شركات',
            'avg_rate' => 'متوسط نسبة العمولة',
            'average' => 'متوسط',
            'this_month' => 'هذا الشهر',
        ],

        'sections' => [
            'default_rate' => [
                'title' => 'نسبة العمولة الافتراضية',
                'description' => 'سيتم تطبيق هذه النسبة على جميع الشركات التي تستخدم النسبة الافتراضية',
            ],
            'company_rates' => [
                'title' => 'نسب عمولة الشركات',
                'description' => 'جميع الشركات مع نسب العمولة الخاصة بها. قم بالتعديل لتعيين نسبة مخصصة أو افتراضية.',
            ],
        ],

        'fields' => [
            'default_rate' => 'النسبة الافتراضية',
            'default_rate_helper' => 'نسبة العمولة الافتراضية المطبقة على الشركات التي ليس لديها نسبة مخصصة',
            'commission_rate' => 'نسبة العمولة',
            'company' => 'الشركة',
            'rate_type' => 'نوع النسبة',
            'rate_type_default' => 'النسبة الافتراضية (:rate)',
            'rate_type_custom' => 'نسبة مخصصة',
        ],

        'table' => [
            'company_name' => 'الشركة',
            'rate' => 'النسبة',
            'rate_type' => 'النوع',
            'custom' => 'مخصص',
            'default' => 'افتراضي',
            'trips' => 'الرحلات',
            'trips_label' => 'رحلة',
            'revenue' => 'الإيرادات',
            'commission' => 'العمولة',
            'empty_heading' => 'لا توجد شركات',
            'empty_description' => 'لم يتم إنشاء أي شركات بعد.',
        ],

        'actions' => [
            'save_default_rate' => 'حفظ النسبة الافتراضية',
            'edit_rate' => 'تعديل',
        ],

        'notifications' => [
            'default_rate_saved' => 'تم حفظ نسبة العمولة الافتراضية وتطبيقها على جميع الشركات التي تستخدم النسبة الافتراضية',
            'rate_updated' => 'تم تحديث نسبة العمولة بنجاح',
            'error' => 'حدث خطأ',
        ],
    ],
];
