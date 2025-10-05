<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\CheckAdminEnabledMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ApiPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('api')
            ->path('/')
            ->authGuard('web')
            ->login()
            ->domain(config('app.domains.api'))
            ->colors([
                'primary' => '#DA1F26',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                CheckAdminEnabledMiddleware::class,
            ])
            ->brandLogo(asset('/images/logo/dark.svg'))
            ->darkModeBrandLogo(asset('/images/logo/light.svg'))
            ->favicon(asset('images/logo/light.svg', ! App::isLocal()))
            ->brandLogoHeight('3rem')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
