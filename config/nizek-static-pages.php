<?php

declare(strict_types=1);

use App\Filament\StaticPage\CustomFormFields;
use App\Filament\StaticPage\CustomTableColumns;
use Nizek\StaticPage\Http\Controllers\StaticPageShowContentController;
use Nizek\StaticPage\Http\Services\LanguageDetectorService;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value defines the name of your application and is used across
    | various parts of the system including static pages, metadata, and
    | other locations where the app identity is shown.
    |
    */

    'app_name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Filament Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the appearance and behavior of resources in the Filament admin
    | panel, including the sidebar navigation group name, icon, and sorting order.
    |
    */

    'filament' => [
        'navigation' => [
            'group' => 'App Management',
            'icon' => 'heroicon-o-document',
            'sort' => 11,
        ],
        // For now only support false
        'multi_tenancy' => [
            'has' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Control how and if the HTTP routes provided by the package are registered.
    | You can configure each route's URI, HTTP method, controller, middleware,
    | name, and any additional parameters such as view templates.
    |
    | To register a new route:
    | Simply add a new entry to the 'routes.list' array with the required keys
    | (method, uri, action, name, middlewares, parameters). It will be
    | automatically registered if 'routes.enabled' is set to true.
    |
    */

    'routes' => [

        // Enable or disable auto-registration of default package routes
        'enabled' => true,

        'list' => [

            // Route for displaying static page content by slug
            'content_show' => [
                'method' => 'get',
                'domain' => null,
                'uri' => 'static-pages/content/{staticPage:slug}',
                'action' => StaticPageShowContentController::class,
                'name' => 'content.show',
                'middlewares' => ['web'],
                'parameters' => [
                    'blade_view' => 'nizek-static-pages::static-page',
                ],
            ],
        ],

        'view' => [
            'enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Detection
    |--------------------------------------------------------------------------
    |
    | Configure how the system determines if the active language is Arabic.
    | This is helpful for RTL support, layout direction, and localization.
    | You can use a custom handler class or stick with the built-in locale logic.
    |
    */

    'language_detection' => [
        'handler' => LanguageDetectorService::class,
    ],

    'tenancy' => [
        'enabled' => env('STATIC_PAGES_TENANCY_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Form Fields
    |--------------------------------------------------------------------------
    |
    | Register a custom class that implements CustomFormFieldsContract to add
    | your own form fields to the static page form. These fields will be
    | inserted before the content fields.
    |
    | Example:
    | 'custom_fields_class' => \App\StaticPage\CustomFields::class,
    |
    | Your class must implement:
    | Nizek\StaticPage\Contracts\CustomFormFieldsContract
    |
    */

    'custom_fields_class' => CustomFormFields::class,

    /*
    |--------------------------------------------------------------------------
    | Custom Table Columns
    |--------------------------------------------------------------------------
    |
    | Register a custom class that implements CustomTableColumnsContract to add
    | your own columns to the static pages table. These columns will be
    | inserted after the enabled column.
    |
    | Example:
    | 'custom_table_columns_class' => \App\StaticPage\CustomTableColumns::class,
    |
    | Your class must implement:
    | Nizek\StaticPage\Contracts\CustomTableColumnsContract
    |
    */

    'custom_table_columns_class' => CustomTableColumns::class,
];
