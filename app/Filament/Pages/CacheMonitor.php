<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Services\Cache\AppStateCache;
use App\Services\Cache\RiderCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
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
        $this->onlineRiders = array_slice(RiderCache::getOnline(), 0, 50);
        $this->busyRiders = array_slice(RiderCache::getBusy(), 0, 50);
    }

    protected function getCacheStats(): array
    {
        return [
            'cache_driver' => config('cache.default'),
            'total_riders' => RiderCache::count(),
            'online_riders' => RiderCache::countByStatus('online'),
            'busy_riders' => RiderCache::countByStatus('busy'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('loadData'),

            Action::make('flushAppState')
                ->label('Flush App State')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Flush App State Cache?')
                ->modalDescription('This will remove all customer and rider app state from cache.')
                ->action(function () {
                    AppStateCache::flush();
                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('App state cache flushed')
                        ->send();
                }),

            Action::make('flushRiders')
                ->label('Flush Riders')
                ->icon('heroicon-o-users')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Flush Riders Cache?')
                ->modalDescription('This will remove all rider data (status, location) from cache.')
                ->action(function () {
                    RiderCache::flush();
                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('Riders cache flushed')
                        ->send();
                }),

            Action::make('flushAll')
                ->label('Flush All Caches')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Flush All Caches?')
                ->modalDescription('This will remove ALL cache data. This action cannot be undone.')
                ->action(function () {
                    // Flush all cache tags
                    Cache::flush();

                    // Flush specific caches
                    AppStateCache::flush();
                    RiderCache::flush();

                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('All caches flushed successfully')
                        ->send();
                }),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Cache Monitor';
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.settings');
    }

    public function getTitle(): string
    {
        return 'Cache Monitor';
    }

    public function getHeading(): string
    {
        return 'Cache Monitor';
    }
}
