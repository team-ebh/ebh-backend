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
    'App\Http\Controllers\Api\V1\Customer\TripController', // Allow custom methods in TripController
    'App\Http\Controllers\Api\V1\Customer\AuthController', // Allow custom methods in AuthController
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
        'App\Http\Controllers',
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
