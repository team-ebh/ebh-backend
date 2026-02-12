<x-filament-panels::page>
    {{-- Stats Row --}}
    <div class="grid grid-cols-6 gap-3 mb-4">
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase">Driver</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ strtoupper($stats['cache_driver'] ?? 'N/A') }}</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase">Memory</div>
            <div class="text-sm font-semibold text-purple-600 dark:text-purple-400">{{ $stats['redis_memory'] ?? 'N/A' }}</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase">Peak</div>
            <div class="text-sm font-semibold text-purple-600 dark:text-purple-400">{{ $stats['redis_peak_memory'] ?? 'N/A' }}</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase">Total</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_riders'] ?? 0) }}</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                <span class="inline-block w-1.5 h-1.5 bg-green-500 rounded-full"></span> Online
            </div>
            <div class="text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($stats['online_riders'] ?? 0) }}</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                <span class="inline-block w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Busy
            </div>
            <div class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['busy_riders'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-3 gap-4">
        {{-- Cache Scopes (Left Column) --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
            <h3 class="mb-3 text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wide">Cache Layers</h3>
            <div class="space-y-2">
                @foreach($stats['scopes'] ?? [] as $scope)
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-1">
                            <div class="text-xs font-medium text-gray-900 dark:text-white">{{ $scope['name'] }}</div>
                            <div class="text-[10px] font-semibold text-blue-600 dark:text-blue-400">{{ $scope['ttl'] }}</div>
                        </div>
                        <div class="text-[10px] text-gray-600 dark:text-gray-400">{{ $scope['description'] }}</div>
                        <div class="mt-1 text-[10px] font-mono text-gray-500 dark:text-gray-500">{{ $scope['scope'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-[10px] text-gray-500 dark:text-gray-400 space-y-1">
                    <div>Redis: {{ $stats['redis_host'] ?? 'N/A' }}:{{ $stats['redis_port'] ?? 'N/A' }}</div>
                    <div>Auto-refresh: 5s</div>
                    <div class="font-mono">{{ now()->format('H:i:s') }}</div>
                </div>
            </div>
        </div>

        {{-- Online Riders (Middle Column) --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wide">Online Riders</h3>
                <span class="text-[10px] text-gray-500 dark:text-gray-400">Top 50</span>
            </div>

            @if(count($onlineRiders) > 0)
                <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded">
                    <div class="overflow-y-auto" style="max-height: 500px;">
                        <table class="min-w-full text-[11px]">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                <tr>
                                    <th class="px-2 py-1.5 text-left text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                                    <th class="px-2 py-1.5 text-left text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                    <th class="px-2 py-1.5 text-left text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">Lat/Lng</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($onlineRiders as $rider)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-2 py-1.5 font-mono text-gray-900 dark:text-white">{{ $rider['rider_id'] ?? 'N/A' }}</td>
                                        <td class="px-2 py-1.5 text-gray-900 dark:text-white truncate" style="max-width: 100px;">{{ $rider['name'] ?? 'N/A' }}</td>
                                        <td class="px-2 py-1.5 font-mono text-gray-600 dark:text-gray-400">
                                            @if(isset($rider['lat']) && isset($rider['lng']))
                                                {{ number_format($rider['lat'], 3) }},{{ number_format($rider['lng'], 3) }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="py-12 text-center text-xs text-gray-500 dark:text-gray-400">No online riders</div>
            @endif
        </div>

        {{-- Busy Riders (Right Column) --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wide">Busy Riders</h3>
                <span class="text-[10px] text-gray-500 dark:text-gray-400">Top 50</span>
            </div>

            @if(count($busyRiders) > 0)
                <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded">
                    <div class="overflow-y-auto" style="max-height: 500px;">
                        <table class="min-w-full text-[11px]">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                <tr>
                                    <th class="px-2 py-1.5 text-left text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                                    <th class="px-2 py-1.5 text-left text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                    <th class="px-2 py-1.5 text-left text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">Lat/Lng</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($busyRiders as $rider)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-2 py-1.5 font-mono text-gray-900 dark:text-white">{{ $rider['rider_id'] ?? 'N/A' }}</td>
                                        <td class="px-2 py-1.5 text-gray-900 dark:text-white truncate" style="max-width: 100px;">{{ $rider['name'] ?? 'N/A' }}</td>
                                        <td class="px-2 py-1.5 font-mono text-gray-600 dark:text-gray-400">
                                            @if(isset($rider['lat']) && isset($rider['lng']))
                                                {{ number_format($rider['lat'], 3) }},{{ number_format($rider['lng'], 3) }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="py-12 text-center text-xs text-gray-500 dark:text-gray-400">No busy riders</div>
            @endif
        </div>
    </div>

    {{-- Auto-refresh script --}}
    <script>
        setInterval(() => {
            @this.call('loadData');
        }, 5000);
    </script>
</x-filament-panels::page>
