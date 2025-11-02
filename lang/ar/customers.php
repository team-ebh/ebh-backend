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
            'account_disabled' => 'حسابك معطل.',
            'invalid_otp' => 'رمز التحقق غير صالح أو منتهي الصلاحية.',
            'before_registered' => 'العميل مسجل مسبقاً.',
            'must_be_registered' => 'يجب تسجيل العميل أولاً.',
        ],
        'status' => [
            CustomerStatusEnum::PENDING_VERIFICATION->name => 'في انتظار التحقق',
            CustomerStatusEnum::ACTIVE->name => 'نشط',
        ],
    ],
];
