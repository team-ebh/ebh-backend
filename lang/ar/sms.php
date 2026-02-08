<?php

declare(strict_types=1);

return [
    // قوالب رسائل SMS
    'signup' => 'رمز التحقق الخاص بك هو: :otp',
    'signin' => 'رمز التحقق الخاص بك هو: :otp',
    'delete_account' => 'رمز التحقق من حذف الحساب الخاص بك هو: :otp',

    // لوحة الإدارة - سجلات الرسائل القصيرة
    'resource' => [
        'label' => 'سجل رسالة قصيرة',
        'plural_label' => 'سجلات الرسائل القصيرة',
        'navigation_label' => 'سجلات الرسائل القصيرة',
    ],

    // أعمدة الجدول
    'table' => [
        'receiver_type' => 'نوع المستلم',
        'receiver' => 'المستلم',
        'deleted_user' => 'مستخدم محذوف',
        'phone_number' => 'رقم الهاتف',
        'sms_type' => 'نوع الرسالة',
        'provider' => 'مزود الخدمة',
        'status' => 'الحالة',
        'http_status' => 'حالة HTTP',
        'message' => 'الرسالة',
        'sent_at' => 'تاريخ الإرسال',
        'created_at' => 'تاريخ الإنشاء',
    ],

    // تسميات قائمة المعلومات
    'infolist' => [
        'phone_number' => 'رقم الهاتف',
        'delivery_status' => 'حالة التسليم',
        'type' => 'النوع',
        'receiver' => 'المستلم',
        'deleted' => 'محذوف',
        'provider' => 'مزود الخدمة',
        'sent_at' => 'تاريخ الإرسال',
        'http_status' => 'حالة HTTP',
        'sms_message' => 'محتوى الرسالة',
        'request_payload' => 'بيانات الطلب',
        'provider_response' => 'استجابة المزود',
        'technical_details' => 'التفاصيل الفنية',
        'no_message' => 'لا يوجد محتوى رسالة متاح',
        'no_request_data' => 'لا توجد بيانات طلب',
        'no_response_data' => 'لا توجد بيانات استجابة',
        'phone_copied' => 'تم نسخ رقم الهاتف!',
        'request_copied' => 'تم نسخ بيانات الطلب!',
        'response_copied' => 'تم نسخ بيانات الاستجابة!',
    ],

    // المرشحات
    'filters' => [
        'status' => 'الحالة',
        'all' => 'الكل',
        'success' => 'نجح',
        'failed' => 'فشل',
        'sms_type' => 'نوع الرسالة',
        'provider' => 'مزود الخدمة',
        'receiver_type' => 'نوع المستلم',
    ],

    // أنواع الرسائل
    'types' => [
        'signup' => 'التسجيل',
        'signin' => 'تسجيل الدخول',
        'delete_account' => 'حذف الحساب',
    ],

    // مزودو الخدمة
    'providers' => [
        'kwt_sms' => 'KWT SMS',
        'route_mobile' => 'Route Mobile',
    ],

    // أنواع المستلمين
    'receiver_types' => [
        'customer' => 'عميل',
        'rider' => 'سائق',
    ],
];
