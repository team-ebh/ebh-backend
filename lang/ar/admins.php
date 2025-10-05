<?php

declare(strict_types=1);

return [
    'admin' => [
        'fields' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'phone_number' => 'رقم الهاتف',
            'enabled' => 'مفعل؟',
        ],
        'model_label' => 'المديرين',
        'plural_model_label' => 'المديرين',
        'navigation_label' => 'المديرين',
        'actions' => [
            'reset_password' => 'إعادة تعيين كلمة المرور',
            'new_password' => 'كلمة المرور الجديدة',
            'confirm_new_password' => 'تأكيد كلمة المرور الجديدة',
            'password_reset_success' => 'تم إعادة تعيين كلمة المرور بنجاح.',
        ],
    ],
    'api' => [

    ],
];
