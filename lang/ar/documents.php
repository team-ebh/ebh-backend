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
        'status' => [
            'no_file_uploaded' => 'لم يتم رفع ملف',
            'file_not_accessible' => 'الملف موجود ولكن لا يمكن الوصول إليه. يرجى التحقق من إعدادات الخادم.',
            'expired' => 'منتهي الصلاحية',
            'expires_soon' => 'تنتهي صلاحيته خلال :days يوم',
        ],
    ],
];
