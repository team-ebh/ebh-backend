<?php

declare(strict_types=1);

return [
    'admin' => [
        'name' => "Tech's Admin",
        'email' => env('ADMINISTRATOR_ACCOUNT_EMAIL', 'tech-ebh@nizek.com'),
        'password' => env('ADMINISTRATOR_ACCOUNT_PASSWORD', 'N1ZEKezAd@TEB!!%xH%Z!!W$#0'),
    ],
    'client' => [
        'name' => "Client's Admin",
        'email' => env('CLIENT_ACCOUNT_EMAIL', 'client-ebh@admin.com'),
        'password' => env('CLIENT_ACCOUNT_PASSWORD', 'N1ZeKezAd@EB!t%H%Z!$!#0'),
    ],
];
