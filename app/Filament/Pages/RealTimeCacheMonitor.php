<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Services\RealTimeCache\Fallback\RedisHealthChecker;
use App\Services\RealTimeCache\RealTimeCacheManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Redis;

class RealTimeCacheMonitor extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'realtime-cache-monitor';

    protected string $view = 'filament.pages.realtime-cache-monitor';

    public array $stats = [];

    public array $riderLocations = [];

    public array $riderStatuses = [];

    public array $activeTrips = [];

    public array $redisInfo = [];

    public array $healthStatus = [];

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

    public function loadData(): void
    {
        /** @var RealTimeCacheManager $cache */
        $cache = app(RealTimeCacheManager::class);

        $this->stats = $cache->getStats();
        $this->riderLocations = $this->getRiderLocationsData($cache);
        $this->riderStatuses = $this->getRiderStatusesData($cache);
        $this->activeTrips = $this->getActiveTripsData($cache);
        $this->redisInfo = $this->getRedisInfo();
        $this->healthStatus = $this->getHealthStatus();
    }

    protected function getHealthStatus(): array
    {
        /** @var RedisHealthChecker $healthChecker */
        $healthChecker = app(RedisHealthChecker::class);

        return $healthChecker->getStatus();
    }

    protected function getRiderLocationsData(RealTimeCacheManager $cache): array
    {
        $riderIds = $cache->location()->getAllRiderIds();
        $locations = [];

        foreach (array_slice($riderIds, 0, 50) as $riderId) {
            $coord = $cache->location()->get($riderId);
            if ($coord) {
                $locations[] = [
                    'rider_id' => $riderId,
                    'lat' => $coord->lat(),
                    'lng' => $coord->lng(),
                ];
            }
        }

        return $locations;
    }

    protected function getRiderStatusesData(RealTimeCacheManager $cache): array
    {
        $statuses = [];

        foreach ($cache->status()->getOnlineRiderIds() as $riderId) {
            $meta = $cache->status()->getMeta($riderId);
            $status = $meta['status'] ?? 'online';
            $statuses[] = [
                'rider_id' => $riderId,
                'status' => $status,
                'last_seen' => isset($meta['last_seen']) ? date('H:i:s', (int) $meta['last_seen']) : '-',
                'meta' => $meta,
            ];
        }

        return array_slice($statuses, 0, 50);
    }

    protected function getActiveTripsData(RealTimeCacheManager $cache): array
    {
        $tripIds = $cache->trip()->getAllTripIds();
        $trips = [];

        foreach (array_slice($tripIds, 0, 50) as $tripId) {
            $data = $cache->trip()->get($tripId);
            if ($data) {
                $trips[] = [
                    'trip_id' => $tripId,
                    'status' => $data['_status'] ?? 'unknown',
                    'rider_id' => $data['rider_id'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'created_at' => isset($data['_created_at']) ? date('H:i:s', (int) $data['_created_at']) : '-',
                ];
            }
        }

        return $trips;
    }

    protected function getRedisInfo(): array
    {
        try {
            $connection = config('realtime-cache.redis.connection', 'default');
            $info = Redis::connection($connection)->info();

            return [
                'version' => $info['redis_version'] ?? 'N/A',
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 'N/A',
                'uptime_days' => $info['uptime_in_days'] ?? 'N/A',
                'total_keys' => $this->getTotalKeys(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function getTotalKeys(): int
    {
        try {
            $prefix = config('realtime-cache.redis.prefix', 'rtc:');
            $connection = config('realtime-cache.redis.connection', 'default');
            $keys = Redis::connection($connection)->keys($prefix . '*');

            return count($keys);
        } catch (\Exception) {
            return 0;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('loadData'),

            Action::make('flushLocations')
                ->label('Flush Locations')
                ->icon('heroicon-o-map-pin')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Flush Rider Locations?')
                ->modalDescription('This will remove all rider location data from cache.')
                ->action(function () {
                    /** @var RealTimeCacheManager $cache */
                    $cache = app(RealTimeCacheManager::class);
                    $cache->location()->flush();
                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('Rider locations flushed')
                        ->send();
                }),

            Action::make('flushStatuses')
                ->label('Flush Statuses')
                ->icon('heroicon-o-signal')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Flush Rider Statuses?')
                ->modalDescription('This will remove all rider online/offline/busy status data from cache.')
                ->action(function () {
                    /** @var RealTimeCacheManager $cache */
                    $cache = app(RealTimeCacheManager::class);
                    $cache->status()->flush();
                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('Rider statuses flushed')
                        ->send();
                }),

            Action::make('flushTrips')
                ->label('Flush Trips')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Flush Trip Cache?')
                ->modalDescription('This will remove all active trip data from cache.')
                ->action(function () {
                    /** @var RealTimeCacheManager $cache */
                    $cache = app(RealTimeCacheManager::class);
                    $cache->trip()->flush();
                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('Trip cache flushed')
                        ->send();
                }),

            Action::make('flushAll')
                ->label('Flush All')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Flush All RealTime Cache?')
                ->modalDescription('This will remove ALL data (locations, statuses, trips) from cache. This action cannot be undone.')
                ->action(function () {
                    /** @var RealTimeCacheManager $cache */
                    $cache = app(RealTimeCacheManager::class);
                    $cache->flushAll();
                    $this->loadData();

                    Notification::make()
                        ->success()
                        ->title('All cache flushed successfully')
                        ->send();
                }),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'RealTime Cache';
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.settings');
    }

    public function getTitle(): string
    {
        return 'RealTime Cache Monitor';
    }

    public function getHeading(): string
    {
        return 'RealTime Cache Monitor';
    }
}
