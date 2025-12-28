<?php

declare(strict_types=1);

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;

return [
    'statuses' => [
        PaymentStatusEnum::PENDING->name => 'قيد الانتظار',
        PaymentStatusEnum::PAID->name => 'مدفوع',
        PaymentStatusEnum::FAILED->name => 'فشل',
        PaymentStatusEnum::EXPIRED->name => 'منتهي الصلاحية',
        PaymentStatusEnum::LOCKED->name => 'مقفل',
        PaymentStatusEnum::LOCKED_PAID->name => 'مقفل (مدفوع)',
    ],
    'frontend_statuses' => [
        PaymentStatusEnum::PENDING->name => 'قيد الانتظار',
        PaymentStatusEnum::PAID->name => 'نجح',
        PaymentStatusEnum::FAILED->name => 'فشل',
        PaymentStatusEnum::EXPIRED->name => 'منتهي الصلاحية',
        PaymentStatusEnum::LOCKED->name => 'مقفل',
        PaymentStatusEnum::LOCKED_PAID->name => 'مقفل (مدفوع)',
    ],
    'api' => [
        'payment_methods' => [
            PaymentMethodEnum::KNET->name => 'كي نت',
            PaymentMethodEnum::CASH->name => 'نقدي',
        ],
        'payment_methods_description' => [
            PaymentMethodEnum::KNET->name => 'دفع عبر الإنترنت',
            PaymentMethodEnum::CASH->name => 'ادفع نقدًا للسائق',
        ],
    ],
    'errors' => [
        'payment_link_generation_failed' => 'فشل في إنشاء رابط الدفع',
        'payment_gateway_request_failed' => 'فشل طلب بوابة الدفع',
        'payment_gateway_failure_status' => 'أرجعت بوابة الدفع حالة الفشل',
        'payment_gateway_missing_link' => 'أرجعت بوابة الدفع نجاحًا ولكن رابط الدفع مفقود',
        'payment_not_found' => 'الدفع غير موجود',
        'payment_not_paid' => 'لم يتم دفع المبلغ بعد',
        'payment_already_processed' => 'تمت معالجة الدفع بالفعل',
        'payment_verification_failed' => 'فشل التحقق من الدفع',
    ],
];
