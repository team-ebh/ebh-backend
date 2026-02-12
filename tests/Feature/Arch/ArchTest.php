<?php

declare(strict_types=1);

// Note: PHP preset disabled because it flags Arabic text in seeders as "suspicious"
// The seeders contain legitimate Arabic translations for a bilingual application

arch()->preset()->security()->ignoring('assert');

arch()->preset()->laravel()->ignoring([
    'App\Providers\Filament',
    'App\Http\Middleware\CheckAdminEnabledMiddleware',
    'App\Http\Responses\LoginResponse',
    'App\Http\Resources\Api\SuccessResource',
    'App\Http\Controllers\Controller',
    'App\Http\Controllers\Api\V1\Customer\TripController', // Allow custom methods in TripController
    'App\Http\Controllers\Api\V1\Customer\AuthController', // Allow custom methods in AuthController
    'App\Http\Controllers\Api\V1\Customer\PaymentController', // Allow custom methods in PaymentController
    'App\Http\Controllers\Api\V1\Rider\TripController', // Allow custom methods in Rider TripController
    'App\Http\Controllers\Api\V1\Rider\AuthController', // Allow custom methods in Rider AuthController
    'App\Http\Controllers\Api\V1\Rider\RiderController', // Allow custom methods in RiderController
    'App\Http\Controllers\Api\V1\Customer\CustomerController', // Allow custom methods in CustomerController
    'App\Http\Controllers\Api\V1\Customer\HistoryTripController', // Allow custom methods in HistoryTripController
    'App\Http\Controllers\Api\V1\Rider\HistoryTripController', // Allow custom methods in Rider HistoryTripController
    'App\Http\Controllers\Api\V1\Customer\DeleteAccountController', // Allow custom methods in Customer DeleteAccountController
    'App\Http\Controllers\Api\V1\Rider\DeleteAccountController', // Allow custom methods in Rider DeleteAccountController
    'App\Http\Controllers\Api\V1\Rider\EarningsController', // Allow custom methods in Rider EarningsController
    'App\Http\Controllers\Api\V1\TestSocketController', // Allow custom methods in TestSocketController
    'App\Events\Socket\Customer\TripSearchingForRiderEvent', // Uses custom resource for broadcasting
    'App\Events\Socket\Rider\NewTripRequestEvent', // Uses custom resource for broadcasting
    'App\Events\Socket\Rider\TripRequestCancelledEvent', // Uses custom resource for broadcasting
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
        'App\DTOs',
        'App\Events',
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
        'App\Services\Cache', // Allow cache services to extend BaseCache
        'App\View',
    ]);
