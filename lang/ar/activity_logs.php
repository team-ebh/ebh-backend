<?php

declare(strict_types=1);

return [
    'admin' => [
        'model_label' => 'سجل النشاط',
        'plural_model_label' => 'سجلات النشاط',
        'navigation_label' => 'سجل النشاط',

        'fields' => [
            'event' => 'نوع الحدث',
            'event_type' => 'نوع الحدث',
            'log_type' => 'نوع السجل',
            'date_time' => 'التاريخ والوقت',
            'description' => 'الوصف',
            'subject' => 'الموضوع',
            'subject_model' => 'النموذج',
            'subject_id' => 'المعرف',
            'record_id' => 'معرف السجل',
            'subject_link' => 'عرض السجل',
            'causer' => 'المؤدي',
            'causer_name' => 'الاسم',
            'causer_type' => 'النوع والمعرف',
            'performed_by' => 'تم بواسطة',
            'changes' => 'التغييرات',
            'changes_detail' => 'تفاصيل التغييرات',
            'field' => 'الحقل',
            'previous_value' => 'القيمة السابقة',
            'new_value' => 'القيمة الجديدة',
            'batch' => 'الدفعة',
            'batch_uuid' => 'معرف الدفعة',
        ],

        'table' => [
            'filters' => [
                'log_type' => 'نوع السجل',
                'event' => 'الحدث',
                'model' => 'النموذج',
            ],
            'description' => 'عرض الأنشطة لـ :model #:id',
        ],

        'sections' => [
            'header' => 'معلومات النشاط',
            'subject' => 'معلومات الموضوع',
            'subject_description' => 'السجل الذي تأثر بهذا النشاط',
            'performer' => 'معلومات المؤدي',
            'performer_description' => 'من قام بهذا النشاط',
            'changes' => 'التغييرات',
            'changes_description' => 'عرض تفصيلي لما تم تغييره',
            'batch' => 'معلومات الدفعة',
            'batch_description' => 'الأنشطة المجمعة في عملية دفعة',
        ],

        'events' => [
            'created' => 'تم الإنشاء',
            'updated' => 'تم التحديث',
            'deleted' => 'تم الحذف',
            'restored' => 'تم الاستعادة',
        ],

        'messages' => [
            'no_changes' => 'لا توجد تغييرات مسجلة',
            'no_detailed_changes' => 'لا توجد تغييرات تفصيلية متاحة',
            'system' => 'النظام',
            'copy' => 'نسخ',
            'null' => 'فارغ',
            'empty_array' => 'مصفوفة فارغة',
            'true' => 'صحيح',
            'false' => 'خاطئ',
        ],
    ],
];
