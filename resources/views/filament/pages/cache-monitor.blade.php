<x-filament-panels::page>
    {{-- Quick Stats Row --}}
    <div class="grid grid-cols-2 gap-4 mb-6 lg:grid-cols-6">
        {{-- Cache Driver --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="text-xs text-gray-500 dark:text-gray-400">Driver</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                {{ strtoupper($stats['cache_driver'] ?? 'N/A') }}
            </div>
        </div>

        {{-- Memory Usage --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="text-xs text-gray-500 dark:text-gray-400">Memory</div>
            <div class="mt-1 text-lg font-semibold text-purple-600 dark:text-purple-400">
                {{ $stats['redis_memory'] ?? 'N/A' }}
            </div>
        </div>

        {{-- Peak Memory --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="text-xs text-gray-500 dark:text-gray-400">Peak</div>
            <div class="mt-1 text-lg font-semibold text-purple-600 dark:text-purple-400">
                {{ $stats['redis_peak_memory'] ?? 'N/A' }}
            </div>
        </div>

        {{-- Total Riders --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Riders</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                {{ number_format($stats['total_riders'] ?? 0) }}
            </div>
        </div>

        {{-- Online Riders --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                Online
            </div>
            <div class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">
                {{ number_format($stats['online_riders'] ?? 0) }}
            </div>
        </div>

        {{-- Busy Riders --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-500"></span>
                </span>
                Busy
            </div>
            <div class="mt-1 text-lg font-semibold text-yellow-600 dark:text-yellow-400">
                {{ number_format($stats['busy_riders'] ?? 0) }}
            </div>
        </div>
    </div>

    {{-- Cache Scopes Info --}}
    <div class="mb-6">
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Cache Scopes</h3>
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach($stats['scopes'] ?? [] as $scope)
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $scope['name'] }}</div>
                                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $scope['description'] }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Scope:</span>
                                <span class="ml-1 font-mono text-gray-900 dark:text-white">{{ $scope['scope'] }}</span>
                            </div>
                            <div class="text-xs">
                                <span class="text-gray-500 dark:text-gray-400">TTL:</span>
                                <span class="ml-1 font-semibold text-blue-600 dark:text-blue-400">{{ $scope['ttl'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Riders Data Tables --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Online Riders --}}
        <div>
            <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Online Riders</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Top 50</span>
                </div>

                @if(count($onlineRiders) > 0)
                    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="overflow-y-auto" style="max-height: 400px;">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 dark:text-gray-400">ID</th>
                                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 dark:text-gray-400">Name</th>
                                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 dark:text-gray-400">Location</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($onlineRiders as $rider)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                            <td class="px-3 py-2 text-xs font-mono text-gray-900 dark:text-white">
                                                {{ $rider['rider_id'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-900 dark:text-white">
                                                {{ $rider['name'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-3 py-2 text-xs font-mono text-gray-600 dark:text-gray-400">
                                                @if(isset($rider['lat']) && isset($rider['lng']))
                                                    {{ number_format($rider['lat'], 4) }}, {{ number_format($rider['lng'], 4) }}
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="py-8 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">No online riders</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Busy Riders --}}
        <div>
            <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Busy Riders</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Top 50</span>
                </div>

                @if(count($busyRiders) > 0)
                    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="overflow-y-auto" style="max-height: 400px;">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 dark:text-gray-400">ID</th>
                                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 dark:text-gray-400">Name</th>
                                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 dark:text-gray-400">Location</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($busyRiders as $rider)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                            <td class="px-3 py-2 text-xs font-mono text-gray-900 dark:text-white">
                                                {{ $rider['rider_id'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-900 dark:text-white">
                                                {{ $rider['name'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-3 py-2 text-xs font-mono text-gray-600 dark:text-gray-400">
                                                @if(isset($rider['lat']) && isset($rider['lng']))
                                                    {{ number_format($rider['lat'], 4) }}, {{ number_format($rider['lng'], 4) }}
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="py-8 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">No busy riders</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- System Info Footer --}}
    <div class="mt-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                <div class="flex items-center gap-4">
                    <span>Redis: {{ $stats['redis_host'] ?? 'N/A' }}:{{ $stats['redis_port'] ?? 'N/A' }}</span>
                    <span class="text-gray-400">|</span>
                    <span>Auto-refresh every 5 seconds</span>
                </div>
                <div>
                    Last updated: <span class="font-mono">{{ now()->format('H:i:s') }}</span>
                </div>
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
