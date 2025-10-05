<?php

declare(strict_types=1);

return [
    'admin' => [
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'phone_number' => 'Phone Number',
            'enabled' => 'Is Enable?',
        ],
        'model_label' => 'Admins',
        'plural_model_label' => 'Admins',
        'navigation_label' => 'Admins',
        'actions' => [
            'reset_password' => 'Reset Password',
            'new_password' => 'New Password',
            'confirm_new_password' => 'Confirm New Password',
            'password_reset_success' => 'Password has been reset successfully.',
        ],
    ],
    'api' => [

    ],
];
