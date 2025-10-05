<?php

declare(strict_types=1);

return [
    'guest' => env('GUEST_RATE_LIMIT', 80),
    'logged_in_user' => env('LOGGED_IN_USER_RATE_LIMIT', 200),
    'bypass' => env('RATE_LIMITER_BYPASS', 'fb81a77b-c0e5-44c1-bd36-42e8b258e8b1'),
];
