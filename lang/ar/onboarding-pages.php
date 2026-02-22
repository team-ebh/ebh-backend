<?php

declare(strict_types=1);

return [
    'admin' => [
        'model_label' => 'صفحة الترحيب',
        'plural_model_label' => 'صفحات الترحيب',
        'navigation_label' => 'صفحات الترحيب',
        'application_type' => 'نوع التطبيق',
        'application_types' => [
            'customer' => 'تطبيق العميل',
            'rider' => 'تطبيق السائق',
        ],
        'fields' => [
            'name' => 'الاسم',
            'application_type' => 'نوع التطبيق',
            'enabled' => 'مفعّل',
            'banners' => 'اللافتات',
            'banner_title' => 'العنوان (إنجليزي)',
            'banner_title_ar' => 'العنوان (عربي)',
            'banner_subtitle' => 'العنوان الفرعي (إنجليزي)',
            'banner_subtitle_ar' => 'العنوان الفرعي (عربي)',
            'banner_image' => 'صورة اللافتة (إنجليزي)',
            'banner_image_ar' => 'صورة اللافتة (عربي)',
            'banner_sort' => 'الترتيب',
        ],
        'sections' => [
            'general' => 'المعلومات العامة',
            'banners' => 'اللافتات',
        ],
        'hints' => [
            'enabled' => 'يمكن تفعيل صفحة ترحيب واحدة فقط لكل نوع تطبيق.',
            'banners' => 'بين 2 و 5 لافتات. اسحب لإعادة الترتيب.',
        ],
    ],
];
