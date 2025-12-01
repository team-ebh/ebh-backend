<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\Handler;
use App\Http\Responses\LoginResponse;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\RiderLocationRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Admin;
use App\Repositories\Api\V1\Customer\CustomerRepository;
use App\Repositories\Api\V1\Customer\Trip\CustomerTripRepository;
use App\Repositories\Api\V1\Customer\Trip\RiderLocationRepository;
use App\Repositories\Api\V1\Customer\Trip\TripRepository;
use App\Repositories\Api\V1\Rider\RiderRepository;
use App\Repositories\Api\V1\Rider\Trip\RiderTripRepository;
use App\Repositories\TripRequestRepository;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\Example;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as FilamentLoginResponse;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        FilamentLoginResponse::class => LoginResponse::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        CustomerTripRepositoryInterface::class => CustomerTripRepository::class,
        TripRepositoryInterface::class => TripRepository::class,
        RiderLocationRepositoryInterface::class => RiderLocationRepository::class,
        RiderRepositoryInterface::class => RiderRepository::class,
        RiderTripRepositoryInterface::class => RiderTripRepository::class,
        TripRequestRepositoryInterface::class => TripRequestRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->laravelConfiguration();

        $this->filamentConfiguration();

        $this->apiDocsConfiguration();

        $this->apiConfiguration();
    }

    private function laravelConfiguration(): void
    {
        $this->registerRateLimiter();

        $this->registerTelescopeForLocal();

        Model::unguard();
        Model::shouldBeStrict();
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventAccessingMissingAttributes();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        if (! app()->isLocal()) {
            URL::forceScheme('https');
        }
    }

    private function filamentConfiguration(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): string => Blade::render('filament/components/nizek-sidebar-nav-footer')
        );

        Column::configureUsing(function (Column $column): void {
            if ($column instanceof TextColumn) {
                $column
                    ->formatStateUsing(fn ($state) => is_string($state) ? Str::limit($state, 25, '…') : $state)
                    ->placeholder('-')
                    ->searchable();
            }

            $column->toggleable();
        });

        TextEntry::configureUsing(function (TextEntry $textEntry): void {
            $textEntry
                ->placeholder('-');
        });

        FilamentColor::register(Color::all());

        FilamentIcon::register([
            'panels::sidebar.expand-button' => 'heroicon-o-bars-3',
            'panels::sidebar.collapse-button' => 'heroicon-o-bars-3',
        ]);

        Password::defaults(function () {
            return Password::min(6);
        });

        CreateAction::configureUsing(fn ($action) => $action->slideOver());

        EditAction::configureUsing(fn ($action) => $action->slideOver());

        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultSort('id', 'desc')
                ->filtersLayout(FiltersLayout::AboveContentCollapsible)
                ->filtersFormColumns(3)
                ->deferLoading()
                ->paginationPageOptions([10, 25]);
        });
    }

    public function apiDocsConfiguration(): void
    {
        Gate::define('viewApiDocs', function (Admin $admin) {
            return $admin->{Admin::COLUMN_EMAIL} === config('auth-credentials.admin.email');
        });

        Scramble::ignoreDefaultRoutes();

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });

        Scramble::configure()
            ->withOperationTransformers(function (Operation $operation) {
                $languageHeader = (new Parameter('Language', 'header'))
                    ->required(true)
                    ->description('Language of the representation content');

                $languageHeader->examples = [
                    'English' => new Example('en'),
                    'Arabic' => new Example('ar'),
                ];

                $operation->addParameters([$languageHeader]);
            });

        Scramble::registerApi('v1-riders', config('scramble-riders'));
        Scramble::registerApi('v1-customers', config('scramble-customers'));
    }

    private function apiConfiguration(): void
    {
        $this->app->singleton(
            ExceptionHandler::class,
            Handler::class
        );
    }

    private function registerRateLimiter(): void
    {
        RateLimiter::for('limiter', function (Request $request) {
            if (App::runningUnitTests()) {
                return Limit::none();
            }

            $user = $request->user();

            $index = $user?->id ?? $request->ip();

            if (
                $request->hasHeader('bypass-limiter')
                && $request->header('bypass-limiter') === config('rate-limiter.bypass')
            ) {
                return Limit::none()->by($index);
            }

            if (! $user) {
                $limit = config('rate-limiter.guest');
            } else {
                $limit = config('rate-limiter.logged_in_user');
            }

            return $limit ? Limit::perMinute($limit)->by($index) : Limit::none()->by($index);
        });
    }

    public function registerTelescopeForLocal(): void
    {
        if (app()->isLocal() && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
