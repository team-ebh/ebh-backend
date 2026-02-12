<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Overview --}}
        <x-filament::section>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                {{-- Cache Driver --}}
                <div class="space-y-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.stats.cache_driver') }}</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ strtoupper($stats['cache_driver'] ?? 'N/A') }}</dd>
                    <dd class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['redis_host'] ?? 'N/A' }}:{{ $stats['redis_port'] ?? 'N/A' }}</dd>
                </div>

                {{-- Memory Usage --}}
                <div class="space-y-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.stats.memory_usage') }}</dt>
                    <dd class="text-2xl font-semibold text-purple-600 dark:text-purple-400">{{ $stats['redis_memory'] ?? 'N/A' }}</dd>
                    <dd class="text-xs text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.stats.peak_memory') }}: {{ $stats['redis_peak_memory'] ?? 'N/A' }}</dd>
                </div>

                {{-- Total Riders --}}
                <div class="space-y-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.stats.total_riders') }}</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_riders'] ?? 0) }}</dd>
                    <dd class="text-xs text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.stats.in_cache') }}</dd>
                </div>

                {{-- Rider Status --}}
                <div class="space-y-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.stats.rider_status') }}</dt>
                    <dd class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <x-filament::badge color="success">{{ trans('general.cache_monitor.stats.online') }}</x-filament::badge>
                            </span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($stats['online_riders'] ?? 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <x-filament::badge color="warning">{{ trans('general.cache_monitor.stats.busy') }}</x-filament::badge>
                            </span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($stats['busy_riders'] ?? 0) }}</span>
                        </div>
                    </dd>
                </div>
            </div>
        </x-filament::section>

        {{-- Cache Layers --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ trans('general.cache_monitor.cache_layers.heading') }}
            </x-slot>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach($stats['scopes'] ?? [] as $scope)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/50">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ $scope['name'] }}</h3>
                            <x-filament::badge color="info">{{ $scope['ttl'] }}</x-filament::badge>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $scope['description'] }}</p>
                        <code class="text-xs text-gray-500 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $scope['scope'] }}</code>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Riders Tables --}}
        <div class="space-y-6">
            {{-- Online Riders --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="flex-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            {{ trans('general.cache_monitor.riders.online_heading') }}
                        </h3>
                    </div>
                    <x-filament::badge>{{ trans('general.cache_monitor.riders.count', ['count' => count($onlineRiders)]) }}</x-filament::badge>
                </div>

                @if(count($onlineRiders) > 0)
                    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/10">
                                <thead class="bg-gray-50 dark:bg-white/5">
                                    <tr>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.id') }}</span>
                                        </th>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.name') }}</span>
                                        </th>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.location') }}</span>
                                        </th>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.actions') }}</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                    @foreach($onlineRiders as $rider)
                                        <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5">
                                            <td class="fi-ta-cell px-3 py-4">
                                                <div class="text-sm font-mono text-gray-950 dark:text-white">{{ $rider['rider_id'] ?? '-' }}</div>
                                            </td>
                                            <td class="fi-ta-cell px-3 py-4">
                                                <div class="text-sm text-gray-950 dark:text-white">{{ $rider['name'] ?? '-' }}</div>
                                            </td>
                                            <td class="fi-ta-cell px-3 py-4">
                                                <div class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                                    @if(isset($rider['lat']) && isset($rider['lng']))
                                                        {{ number_format($rider['lat'], 4) }}, {{ number_format($rider['lng'], 4) }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="fi-ta-cell px-3 py-4">
                                                <a href="{{ \App\Filament\Resources\Riders\RiderResource::getUrl('edit', ['record' => $rider['rider_id']]) }}"
                                                   class="fi-link text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                                    {{ trans('general.cache_monitor.riders.view') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                        <div class="text-center py-12">
                            <x-filament::icon
                                icon="heroicon-o-inbox"
                                class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                            />
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.riders.no_online') }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Busy Riders --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="flex-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            {{ trans('general.cache_monitor.riders.busy_heading') }}
                        </h3>
                    </div>
                    <x-filament::badge>{{ trans('general.cache_monitor.riders.count', ['count' => count($busyRiders)]) }}</x-filament::badge>
                </div>

                @if(count($busyRiders) > 0)
                    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/10">
                                <thead class="bg-gray-50 dark:bg-white/5">
                                    <tr>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.id') }}</span>
                                        </th>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.name') }}</span>
                                        </th>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.location') }}</span>
                                        </th>
                                        <th class="fi-ta-header-cell px-3 py-3.5 text-start">
                                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ trans('general.cache_monitor.riders.actions') }}</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                    @foreach($busyRiders as $rider)
                                        <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5">
                                            <td class="fi-ta-cell px-3 py-4">
                                                <div class="text-sm font-mono text-gray-950 dark:text-white">{{ $rider['rider_id'] ?? '-' }}</div>
                                            </td>
                                            <td class="fi-ta-cell px-3 py-4">
                                                <div class="text-sm text-gray-950 dark:text-white">{{ $rider['name'] ?? '-' }}</div>
                                            </td>
                                            <td class="fi-ta-cell px-3 py-4">
                                                <div class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                                    @if(isset($rider['lat']) && isset($rider['lng']))
                                                        {{ number_format($rider['lat'], 4) }}, {{ number_format($rider['lng'], 4) }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="fi-ta-cell px-3 py-4">
                                                <a href="{{ \App\Filament\Resources\Riders\RiderResource::getUrl('edit', ['record' => $rider['rider_id']]) }}"
                                                   class="fi-link text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                                    {{ trans('general.cache_monitor.riders.view') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                        <div class="text-center py-12">
                            <x-filament::icon
                                icon="heroicon-o-inbox"
                                class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                            />
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ trans('general.cache_monitor.riders.no_busy') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Auto-refresh script --}}
    <script>
        setInterval(() => {
            @this.call('loadData');
        }, 5000);
    </script>
</x-filament-panels::page>
