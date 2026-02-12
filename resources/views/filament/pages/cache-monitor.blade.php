<x-filament-panels::page>
    {{-- Cache Configuration --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::section>
            <x-slot name="heading">
                Cache Driver
            </x-slot>
            <div class="text-2xl font-bold">
                {{ strtoupper($stats['cache_driver'] ?? 'Unknown') }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $stats['redis_host'] ?? 'localhost' }}:{{ $stats['redis_port'] ?? '6379' }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Redis Memory
            </x-slot>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ $stats['redis_memory'] ?? 'N/A' }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Peak: {{ $stats['redis_peak_memory'] ?? 'N/A' }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    Online Riders
                </div>
            </x-slot>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ number_format($stats['online_riders'] ?? 0) }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Available for trips
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                    </span>
                    Busy Riders
                </div>
            </x-slot>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ number_format($stats['busy_riders'] ?? 0) }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                On active trips
            </div>
        </x-filament::section>
    </div>

    {{-- Cache Scopes --}}
    <x-filament::section class="mb-6">
        <x-slot name="heading">
            Cache Scopes
        </x-slot>
        <x-slot name="description">
            Different cache layers with their TTL configurations
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            @foreach($stats['scopes'] ?? [] as $scope)
                <div class="border rounded-lg p-4 dark:border-gray-700">
                    <h3 class="font-semibold text-lg mb-2">{{ $scope['name'] }}</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Scope:</span>
                            <span class="font-mono">{{ $scope['scope'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">TTL:</span>
                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $scope['ttl'] }}</span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 mt-2">
                            {{ $scope['description'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Total Riders Summary --}}
    <x-filament::section class="mb-6">
        <x-slot name="heading">
            Riders Summary
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats['total_riders'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Total Cached Riders
                </div>
            </div>

            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($stats['online_riders'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Online ({{ $stats['total_riders'] > 0 ? number_format(($stats['online_riders'] / $stats['total_riders']) * 100, 1) : '0' }}%)
                </div>
            </div>

            <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ number_format($stats['busy_riders'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Busy ({{ $stats['total_riders'] > 0 ? number_format(($stats['busy_riders'] / $stats['total_riders']) * 100, 1) : '0' }}%)
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Online Riders List --}}
    @if(count($onlineRiders) > 0)
        <x-filament::section class="mb-6">
            <x-slot name="heading">
                Online Riders (Top 50)
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="px-4 py-2 text-left">Rider ID</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Location</th>
                            <th class="px-4 py-2 text-left">Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($onlineRiders as $rider)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2 font-mono text-sm">{{ $rider['rider_id'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                        Online
                                    </span>
                                </td>
                                <td class="px-4 py-2 font-mono text-sm">
                                    @if(isset($rider['lat']) && isset($rider['lng']))
                                        {{ number_format($rider['lat'], 6) }}, {{ number_format($rider['lng'], 6) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $rider['name'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Busy Riders List --}}
    @if(count($busyRiders) > 0)
        <x-filament::section>
            <x-slot name="heading">
                Busy Riders (Top 50)
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="px-4 py-2 text-left">Rider ID</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Location</th>
                            <th class="px-4 py-2 text-left">Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($busyRiders as $rider)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2 font-mono text-sm">{{ $rider['rider_id'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                                        Busy
                                    </span>
                                </td>
                                <td class="px-4 py-2 font-mono text-sm">
                                    @if(isset($rider['lat']) && isset($rider['lng']))
                                        {{ number_format($rider['lat'], 6) }}, {{ number_format($rider['lng'], 6) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $rider['name'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Empty State --}}
    @if(count($onlineRiders) === 0 && count($busyRiders) === 0)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="text-gray-400 mb-2">
                    <x-heroicon-o-inbox class="w-16 h-16 mx-auto" />
                </div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    No Riders in Cache
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Rider data will appear here when riders come online
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
