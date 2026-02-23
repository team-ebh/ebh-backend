<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Services\Cache\AppStateCache;
use App\Services\Cache\RiderCache;
use App\Services\Cache\TripCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class CacheMonitor extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'cache-monitor';

    protected string $view = 'filament.pages.cache-monitor';

    public array $stats = [];

    public array $onlineRiders = [];

    public array $busyRiders = [];

    public static function canAccess(): bool
    {
        /** @var Admin|null $admin */
        $admin = auth()->user();

        if (! $admin) {
            return false;
        }

        return $admin->{Admin::COLUMN_EMAIL} === config('auth-credentials.admin.email');
    }

    public function mount(): void
    {
        $this->loadData();
    }

    #[On('refresh-data')]
    public function loadData(): void
    {
        $this->stats = $this->getCacheStats();
        $this->onlineRiders = array_slice(RiderCache::getOnlineCacheOnly(), 0, 50);
        $this->busyRiders = array_slice(RiderCache::getBusyCacheOnly(), 0, 50);
    }

    protected function getCacheStats(): array
    {
        $cacheDriver = config('cache.default');

        // Get Redis info only if using Redis
        $redisMemory = 'N/A';
        $redisPeakMemory = 'N/A';

        if (in_array($cacheDriver, ['redis', 'predis'])) {
            try {
                $redis = Cache::getRedis();
                $info = $redis->info('memory');
                $redisMemory = $info['used_memory_human'] ?? 'N/A';
                $redisPeakMemory = $info['used_memory_peak_human'] ?? 'N/A';
            } catch (\Exception $e) {
                // Redis not available
                $redisMemory = 'Not Available';
                $redisPeakMemory = 'Not Available';
            }
        }

        // Get TTL from cache classes dynamically
        $appStateTtl = $this->formatTtl((new AppStateCache)->getTtl());
        $riderTtl = $this->formatTtl((new RiderCache)->getTtl());
        $tripTtl = $this->formatTtl((new TripCache)->getTtl());

        return [
            // Cache Configuration
            'cache_driver' => $cacheDriver,
            'redis_host' => config('database.redis.default.host'),
            'redis_port' => config('database.redis.default.port'),
            'redis_memory' => $redisMemory,
            'redis_peak_memory' => $redisPeakMemory,

            // RiderCache Stats (cache only, no database fallback)
            'total_riders' => RiderCache::countCacheOnly(),
            'online_riders' => RiderCache::countByStatusCacheOnly('online'),
            'busy_riders' => RiderCache::countByStatusCacheOnly('busy'),

            // Cache Scopes
            'scopes' => [
                [
                    'name' => trans('general.cache_monitor.cache_layers.app_state.name'),
                    'scope' => 'app_state',
                    'ttl' => $appStateTtl,
                    'count' => (new AppStateCache)->countEntries(),
                    'description' => trans('general.cache_monitor.cache_layers.app_state.description'),
                ],
                [
                    'name' => trans('general.cache_monitor.cache_layers.rider.name'),
                    'scope' => 'rider',
                    'ttl' => $riderTtl,
                    'count' => (new RiderCache)->countEntries(),
                    'description' => trans('general.cache_monitor.cache_layers.rider.description'),
                ],
                [
                    'name' => trans('general.cache_monitor.cache_layers.trip.name'),
                    'scope' => 'trip',
                    'ttl' => $tripTtl,
                    'count' => (new TripCache)->countEntries(),
                    'description' => trans('general.cache_monitor.cache_layers.trip.description'),
                ],
            ],
        ];
    }

    /**
     * Format TTL seconds to human-readable format
     */
    protected function formatTtl(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }

        if ($seconds < 3600) {
            $minutes = $seconds / 60;

            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        $hours = $seconds / 3600;

        return $hours . ' hour' . ($hours > 1 ? 's' : '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(trans('general.cache_monitor.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('loadData'),

            ActionGroup::make([
                Action::make('cacheOnlineRiders')
                    ->label(trans('general.cache_monitor.actions.cache_online_riders'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_cache_online_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_cache_online_description'))
                    ->action(function () {
                        Artisan::call('cache:online-riders', ['--online' => true]);
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_cache_online'))
                            ->send();
                    }),

                Action::make('cacheBusyRiders')
                    ->label(trans('general.cache_monitor.actions.cache_busy_riders'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_cache_busy_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_cache_busy_description'))
                    ->action(function () {
                        Artisan::call('cache:online-riders', ['--busy' => true]);
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_cache_busy'))
                            ->send();
                    }),

                Action::make('cacheActiveRiders')
                    ->label(trans('general.cache_monitor.actions.cache_active_riders'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_cache_active_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_cache_active_description'))
                    ->action(function () {
                        Artisan::call('cache:online-riders', ['--active' => true]);
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_cache_active'))
                            ->send();
                    }),

                Action::make('cacheAllRiders')
                    ->label(trans('general.cache_monitor.actions.cache_all_riders'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_cache_all_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_cache_all_description'))
                    ->action(function () {
                        Artisan::call('cache:online-riders', ['--all' => true]);
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_cache_all'))
                            ->send();
                    }),

                Action::make('cacheFreshRiders')
                    ->label(trans('general.cache_monitor.actions.cache_fresh_riders'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_cache_fresh_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_cache_fresh_description'))
                    ->action(function () {
                        Artisan::call('cache:online-riders', ['--fresh' => true]);
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_cache_fresh'))
                            ->send();
                    }),
            ])
                ->label(trans('general.cache_monitor.actions.cache_actions'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),

            ActionGroup::make([
                Action::make('flushAppState')
                    ->label(trans('general.cache_monitor.actions.flush_app_state'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_app_state_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_app_state_description'))
                    ->action(function () {
                        AppStateCache::flush();
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_app_state'))
                            ->send();
                    }),

                Action::make('flushRiders')
                    ->label(trans('general.cache_monitor.actions.flush_riders'))
                    ->icon('heroicon-o-users')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_riders_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_riders_description'))
                    ->action(function () {
                        RiderCache::flush();
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_riders'))
                            ->send();
                    }),

                Action::make('flushTrips')
                    ->label(trans('general.cache_monitor.actions.flush_trips'))
                    ->icon('heroicon-o-map')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_trips_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_trips_description'))
                    ->action(function () {
                        TripCache::flush();
                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_trips'))
                            ->send();
                    }),

                Action::make('flushAll')
                    ->label(trans('general.cache_monitor.actions.flush_all'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(trans('general.cache_monitor.actions.confirm_all_heading'))
                    ->modalDescription(trans('general.cache_monitor.actions.confirm_all_description'))
                    ->action(function () {
                        // Flush specific caches
                        AppStateCache::flush();
                        RiderCache::flush();
                        TripCache::flush();

                        $this->loadData();

                        Notification::make()
                            ->success()
                            ->title(trans('general.cache_monitor.actions.success_all'))
                            ->send();
                    }),
            ])
                ->label(trans('general.cache_monitor.actions.flush_actions'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->button(),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return trans('general.cache_monitor.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.technical_management');
    }

    public function getTitle(): string
    {
        return trans('general.cache_monitor.title');
    }

    public function getHeading(): string
    {
        return trans('general.cache_monitor.title');
    }
}
