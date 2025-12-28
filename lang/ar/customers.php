<?php

declare(strict_types=1);

use App\Enums\Customer\CustomerStatusEnum;

return [
    'api' => [
        'auth' => [
            'first_name' => [
                'required' => 'الاسم الأول مطلوب.',
                'string' => 'يجب أن يكون الاسم الأول نصاً.',
                'max' => 'لا يمكن أن يكون الاسم الأول أكثر من 30 حرفاً.',
            ],
            'last_name' => [
                'required' => 'اسم العائلة مطلوب.',
                'string' => 'يجب أن يكون اسم العائلة نصاً.',
                'max' => 'لا يمكن أن يكون اسم العائلة أكثر من 30 حرفاً.',
            ],
            'email' => [
                'email' => 'يجب أن يكون البريد الإلكتروني عنوان بريد إلكتروني صالح.',
                'max' => 'لا يمكن أن يكون البريد الإلكتروني أكثر من 255 حرفاً.',
            ],
            'phone_number' => [
                'required' => 'رقم الهاتف مطلوب.',
                'unique' => 'رقم الهاتف مسجل مسبقاً.',
                'regex' => 'تنسيق رقم الهاتف غير صالح.',
                'not_found' => 'رقم الهاتف غير موجود أو الحساب غير نشط.',
            ],
            'otp' => [
                'required' => 'رمز التحقق مطلوب.',
                'string' => 'يجب أن يكون رمز التحقق نصاً.',
                'size' => 'يجب أن يكون رمز التحقق 4 أرقام بالضبط.',
            ],
            'otp_sent' => 'تم إرسال رمز التحقق إلى رقم هاتفك.',
            'otp_invalid' => 'رمز التحقق غير صالح أو منتهي الصلاحية.',
            'customer_not_found' => 'العميل غير موجود.',
            'account_disabled' => 'حسابك معطل.',
        ],
        'exceptions' => [
            'not_found' => 'العميل غير موجود.',
            'account_disabled' => 'حسابك موقوف أو غير نشط. يرجى التواصل مع الدعم.',
            'invalid_otp' => 'رمز التحقق غير صالح أو منتهي الصلاحية.',
            'before_registered' => 'العميل مسجل مسبقاً.',
            'must_be_registered' => 'يجب تسجيل العميل أولاً.',
        ],
    ],
    'admin' => [
        'model_label' => 'عميل',
        'plural_model_label' => 'العملاء',
        'navigation_label' => 'العملاء',
        'form' => [
            'personal_information' => 'المعلومات الشخصية',
            'personal_information_description' => 'التفاصيل الأساسية ومعلومات الاتصال للعميل',
        ],
        'fields' => [
            'first_name' => 'الاسم الأول',
            'last_name' => 'اسم العائلة',
            'full_name' => 'الاسم الكامل',
            'email' => 'البريد الإلكتروني',
            'phone_number' => 'رقم الهاتف',
            'status' => 'الحالة',
            'trip_id' => 'رقم الرحلة',
            'trip_status' => 'الحالة',
            'trip_type' => 'النوع',
            'vehicle_type' => 'نوع المركبة',
            'total_price' => 'السعر الإجمالي',
        ],
        'relation_managers' => [
            'trips' => 'الرحلات',
        ],
        'infolist' => [
            'personal_information' => 'المعلومات الشخصية',
            'personal_information_description' => 'التفاصيل الأساسية ومعلومات الاتصال للعميل',
            'status_information' => 'حالة الحساب',
            'status_information_description' => 'الحالة الحالية والإجراءات المتاحة',
            'trips' => 'سجل الرحلات',
            'trips_description' => 'جميع الرحلات التي قام بها هذا العميل',
        ],
        'status' => [
            CustomerStatusEnum::PENDING_VERIFICATION->name => 'في انتظار التحقق',
            CustomerStatusEnum::ACTIVE->name => 'نشط',
            CustomerStatusEnum::INACTIVE->name => 'غير نشط',
            CustomerStatusEnum::SUSPENDED->name => 'معلق',
        ],
        'actions' => [
            'activate' => 'تفعيل',
            'deactivate' => 'إلغاء التفعيل',
            'suspend' => 'تعليق',
        ],
        'notifications' => [
            'activated' => 'تم تفعيل العميل بنجاح.',
            'deactivated' => 'تم إلغاء تفعيل العميل بنجاح.',
            'suspended' => 'تم تعليق العميل بنجاح.',
        ],
        'exceptions' => [
            'has_active_trip' => 'لا يمكن تعطيل العميل. العميل لديه رحلة نشطة قيد التنفيذ.',
            'has_pending_payment' => 'لا يمكن تعطيل العميل. العميل لديه دفعة معلقة يجب إكمالها أولاً.',
        ],
        'stats' => [
            'total_customers' => 'إجمالي العملاء',
            'total_customers_description' => 'جميع العملاء المسجلين',
            'active_customers' => 'العملاء النشطين',
            'active_customers_description' => 'الحسابات النشطة حالياً',
            'new_this_month' => 'الجدد هذا الشهر',
            'new_this_month_description' => 'المسجلين هذا الشهر',
            'suspended_users' => 'المستخدمون المعلقون',
            'suspended_users_description' => 'الحسابات المعلقة',
        ],
    ],
];
