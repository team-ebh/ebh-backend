<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'المستندات',
        'model_label' => 'المستند',
        'plural_model_label' => 'المستندات',
        'fields' => [
            'name' => 'اسم المستند',
            'description' => 'الوصف',
            'validity_period' => 'فترة الصلاحية',
            'accepted_formats' => 'الصيغ المقبولة',
            'is_required' => 'هذا المستند مطلوب لجميع السائقين',
            'is_required_helper' => 'إذا تم تفعيله، سيُطلب هذا المستند عند إنشاء أو تعديل سائق',
            'enabled' => 'مفعل؟',
        ],
    ],
];
