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
            'enabled' => 'مفعل؟',
            'profile_photo' => 'صورة الملف الشخصي',
            'accessibility_certifications' => [
                'label' => 'شهادات إمكانية الوصول',
                'wheelchair_accessible' => 'يمكن الوصول للكرسي المتحرك',
                'arabic_sign_language' => 'لغة الإشارة العربية',
                'visual_assistance' => 'المساعدة البصرية',
                'hearing_assistance' => 'المساعدة السمعية',
            ],
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
        'form' => [
            'personal_information' => 'المعلومات الشخصية',
            'personal_information_description' => 'أدخل المعلومات الشخصية الأساسية للسائق',
            'profile_photo_helper' => 'قم بتحميل صورة ملف شخصي مربعة (حد أقصى 2 ميجابايت). الصيغ المقبولة هي JPG أو JPEG أو PNG.',
            'accessibility_certifications' => 'شهادات إمكانية الوصول',
            'accessibility_certifications_description' => 'حدد شهادات إمكانية الوصول التي يمتلكها هذا السائق',
            'documents' => 'المستندات',
            'documents_description' => 'قم بتحميل المستندات المطلوبة للسائق',
        ],
        'infolist' => [
            'personal_information' => 'المعلومات الشخصية',
            'personal_information_description' => 'التفاصيل الشخصية الأساسية للسائق',
            'status_information' => 'معلومات الحالة',
            'status_information_description' => 'الحالة الحالية وشهادات إمكانية الوصول',
            'vehicle_information' => 'معلومات المركبة',
            'vehicle_information_description' => 'تفاصيل حول مركبة السائق',
            'documents' => 'المستندات',
            'documents_description' => 'المستندات والشهادات المرفوعة',
            'timestamps' => 'طوابع زمنية',
        ],
        'exceptions' => [
            'has_active_trip' => 'لا يمكن تعطيل السائق. السائق لديه رحلة نشطة قيد التنفيذ.',
        ],
    ],
    'api' => [
        'validation' => [
            'decline_reason' => [
                'required' => 'سبب الرفض مطلوب',
                'max' => 'يجب ألا يتجاوز سبب الرفض 500 حرف',
            ],
            'status' => [
                'required' => 'الحالة مطلوبة',
                'in' => 'يجب أن تكون الحالة إما متصل أو غير متصل',
            ],
            'profile' => [
                'full_name' => [
                    'required' => 'الاسم الكامل مطلوب',
                    'string' => 'يجب أن يكون الاسم الكامل نصاً',
                    'min' => 'يجب أن يكون الاسم الكامل حرفين على الأقل',
                    'max' => 'يجب ألا يتجاوز الاسم الكامل 60 حرفاً',
                ],
                'email' => [
                    'email' => 'يرجى إدخال بريد إلكتروني صالح',
                    'max' => 'يجب ألا يتجاوز البريد الإلكتروني 255 حرفاً',
                ],
                'phone_number' => [
                    'required' => 'رقم الهاتف مطلوب',
                    'regex' => 'يجب أن يتكون رقم الهاتف من 8 أرقام بالضبط',
                ],
                'image' => [
                    'required' => 'صورة الملف الشخصي مطلوبة',
                    'image' => 'يجب أن يكون الملف صورة',
                    'mimes' => 'يجب أن تكون الصورة بصيغة JPEG أو JPG أو PNG أو WebP',
                    'max' => 'يجب ألا يتجاوز حجم الصورة 2 ميجابايت',
                ],
            ],
        ],
        'errors' => [
            'trip_not_available' => 'الرحلة لم تعد متاحة',
            'trip_already_assigned' => 'تم تعيين هذه الرحلة بالفعل إلى سائق آخر',
            'rider_not_available' => 'السائق غير متاح',
            'rider_must_be_available' => 'يجب أن تكون متاحًا لقبول الرحلات',
        ],
        'exceptions' => [
            'account_disabled' => 'حسابك معطل. يرجى التواصل مع الدعم.',
            'cannot_change_status' => 'لا يمكن تغيير الحالة. يجب عليك إكمال أو إلغاء رحلتك النشطة أولاً، أو الانتظار حتى لا تكون مشغولاً.',
        ],
        'success' => [
            'trip_accepted' => 'تم قبول الرحلة بنجاح',
            'trip_declined' => 'تم رفض الرحلة بنجاح',
            'status_updated' => 'تم تحديث الحالة بنجاح',
        ],
        'location_updated_successfully' => 'تم تحديث الموقع بنجاح',
    ],
    'location_updated_successfully' => 'تم تحديث الموقع بنجاح',
];
