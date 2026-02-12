<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Overview Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Cache Driver --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Cache Driver</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ strtoupper($stats['cache_driver'] ?? 'N/A') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $stats['redis_host'] ?? 'N/A' }}:{{ $stats['redis_port'] ?? 'N/A' }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Memory Usage --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Memory Usage</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['redis_memory'] ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Peak: {{ $stats['redis_peak_memory'] ?? 'N/A' }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Riders --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Riders</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_riders'] ?? 0) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">In cache memory</p>
                    </div>
                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Status Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Rider Status</p>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Online</span>
                        </div>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($stats['online_riders'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Busy</span>
                        </div>
                        <span class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['busy_riders'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cache Layers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cache Layers</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($stats['scopes'] ?? [] as $scope)
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $scope['name'] }}</h3>
                                <span class="text-xs font-semibold px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded">{{ $scope['ttl'] }}</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $scope['description'] }}</p>
                            <code class="text-xs text-gray-500 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $scope['scope'] }}</code>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Riders Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Online Riders --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Online Riders</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($onlineRiders) }} riders</span>
                    </div>
                </div>
                <div class="p-6">
                    @if(count($onlineRiders) > 0)
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="overflow-y-auto" style="max-height: 400px;">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rider ID</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($onlineRiders as $rider)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">{{ $rider['rider_id'] ?? '-' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $rider['name'] ?? '-' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-400">
                                                    @if(isset($rider['lat']) && isset($rider['lng']))
                                                        {{ number_format($rider['lat'], 4) }}, {{ number_format($rider['lng'], 4) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No online riders</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Busy Riders --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Busy Riders</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($busyRiders) }} riders</span>
                    </div>
                </div>
                <div class="p-6">
                    @if(count($busyRiders) > 0)
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="overflow-y-auto" style="max-height: 400px;">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rider ID</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($busyRiders as $rider)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">{{ $rider['rider_id'] ?? '-' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $rider['name'] ?? '-' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-400">
                                                    @if(isset($rider['lat']) && isset($rider['lng']))
                                                        {{ number_format($rider['lat'], 4) }}, {{ number_format($rider['lng'], 4) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No busy riders</p>
                        </div>
                    @endif
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
