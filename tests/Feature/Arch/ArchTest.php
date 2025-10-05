<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security()->ignoring('assert');

arch()->preset()->laravel()->ignoring([
    'App\Providers\Filament',
    'App\Http\Middleware\CheckAdminEnabledMiddleware',
    'App\Http\Responses\LoginResponse',
    'App\Http\Resources\Api\SuccessResource',
    'App\Http\Controllers\Controller',
]);

arch('strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('avoid inheritance')
    ->expect('App')
    ->classes()
    ->toExtendNothing()
    ->ignoring([
        'App\Console\Commands',
        'App\Exceptions',
        'App\Filament',
        'App\Http\Requests',
        'App\Http\Responses',
        'App\Http\Resources',
        'App\Jobs',
        'App\Livewire',
        'App\Mail',
        'App\Models',
        'App\Notifications',
        'App\Providers',
        'App\View',
    ]);
