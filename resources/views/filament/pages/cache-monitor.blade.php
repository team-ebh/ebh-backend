<x-filament-panels::page>
    {{-- Header Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-4 md:grid-cols-2">
        {{-- Cache Driver --}}
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Cache Driver</h3>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ strtoupper($stats['cache_driver'] ?? 'Unknown') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $stats['redis_host'] ?? 'N/A' }}:{{ $stats['redis_port'] ?? 'N/A' }}
                </p>
            </div>
        </div>

        {{-- Redis Memory --}}
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Memory Usage</h3>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $stats['redis_memory'] ?? 'N/A' }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Peak: {{ $stats['redis_peak_memory'] ?? 'N/A' }}
                </p>
            </div>
        </div>

        {{-- Online Riders --}}
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Online Riders</h3>
                <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($stats['online_riders'] ?? 0) }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Available for trips
                </p>
            </div>
        </div>

        {{-- Busy Riders --}}
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-yellow-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Busy Riders</h3>
                <p class="mt-2 text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ number_format($stats['busy_riders'] ?? 0) }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    On active trips
                </p>
            </div>
        </div>
    </div>

    {{-- Cache Scopes --}}
    <div class="mb-6">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cache Layers</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Different cache scopes with TTL configurations</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach($stats['scopes'] ?? [] as $scope)
                <div class="relative overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $scope['name'] }}</h3>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $scope['description'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Scope</p>
                            <p class="mt-1 font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $scope['scope'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">TTL</p>
                            <p class="mt-1 text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $scope['ttl'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Riders Summary --}}
    <div class="mb-6">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Riders Overview</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Real-time rider status distribution</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            {{-- Total --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Cached</p>
                            <p class="mt-2 text-4xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($stats['total_riders'] ?? 0) }}
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700">
                            <svg class="w-8 h-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Online --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/10 rounded-xl shadow-sm ring-1 ring-green-500/20">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-400">Online</p>
                            <p class="mt-2 text-4xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format($stats['online_riders'] ?? 0) }}
                            </p>
                            <p class="mt-1 text-xs text-green-600/80 dark:text-green-400/80">
                                {{ $stats['total_riders'] > 0 ? number_format(($stats['online_riders'] / $stats['total_riders']) * 100, 1) : '0' }}% of total
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-green-500/20">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Busy --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-900/10 rounded-xl shadow-sm ring-1 ring-yellow-500/20">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-700 dark:text-yellow-400">Busy</p>
                            <p class="mt-2 text-4xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ number_format($stats['busy_riders'] ?? 0) }}
                            </p>
                            <p class="mt-1 text-xs text-yellow-600/80 dark:text-yellow-400/80">
                                {{ $stats['total_riders'] > 0 ? number_format(($stats['busy_riders'] / $stats['total_riders']) * 100, 1) : '0' }}% of total
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-yellow-500/20">
                            <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Online Riders Table --}}
    @if(count($onlineRiders) > 0)
        <div class="mb-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Online Riders</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Showing top 50 online riders</p>
            </div>

            <div class="overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Rider ID</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Location</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($onlineRiders as $rider)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $rider['rider_id'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            Online
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-600 whitespace-nowrap dark:text-gray-400">
                                        @if(isset($rider['lat']) && isset($rider['lng']))
                                            {{ number_format($rider['lat'], 6) }}, {{ number_format($rider['lng'], 6) }}
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $rider['name'] ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Busy Riders Table --}}
    @if(count($busyRiders) > 0)
        <div class="mb-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Busy Riders</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Showing top 50 busy riders</p>
            </div>

            <div class="overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Rider ID</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Location</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($busyRiders as $rider)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $rider['rider_id'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                            Busy
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-600 whitespace-nowrap dark:text-gray-400">
                                        @if(isset($rider['lat']) && isset($rider['lng']))
                                            {{ number_format($rider['lat'], 6) }}, {{ number_format($rider['lng'], 6) }}
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $rider['name'] ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Empty State --}}
    @if(count($onlineRiders) === 0 && count($busyRiders) === 0)
        <div class="overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="px-6 py-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No Riders in Cache</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Rider data will appear here when riders come online
                </p>
            </div>
        </div>
    @endif
</x-filament-panels::page>
