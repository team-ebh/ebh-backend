<?php

declare(strict_types=1);

return [
    'admin' => [
        'name' => "Tech's Admin",
        'email' => env('ADMINISTRATOR_ACCOUNT_EMAIL', 'tech@nizek.com'),
        'password' => env('ADMINISTRATOR_ACCOUNT_PASSWORD', 'N1ZEKezAd@Te!!%x%Z!$!#0'),
    ],
    'client' => [
        'name' => "Client's Admin",
        'email' => env('CLIENT_ACCOUNT_EMAIL', 'client@admin.com'),
        'password' => env('CLIENT_ACCOUNT_PASSWORD', 'N1ZekezAd@E!t%A%Z!$!#0'),
    ],
];
