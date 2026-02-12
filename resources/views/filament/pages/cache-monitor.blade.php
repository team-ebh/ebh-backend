<x-filament-panels::page>
    {{-- Compact Stats Bar --}}
    <div class="flex gap-2 mb-4">
        <div class="flex-1 p-2 bg-white dark:bg-gray-800 rounded">
            <div class="text-[9px] text-gray-500 dark:text-gray-400">Driver</div>
            <div class="text-xs font-semibold">{{ strtoupper($stats['cache_driver'] ?? 'N/A') }}</div>
        </div>
        <div class="flex-1 p-2 bg-white dark:bg-gray-800 rounded">
            <div class="text-[9px] text-gray-500 dark:text-gray-400">Memory</div>
            <div class="text-xs font-semibold text-purple-600 dark:text-purple-400">{{ $stats['redis_memory'] ?? 'N/A' }}</div>
        </div>
        <div class="flex-1 p-2 bg-white dark:bg-gray-800 rounded">
            <div class="text-[9px] text-gray-500 dark:text-gray-400">Peak</div>
            <div class="text-xs font-semibold text-purple-600 dark:text-purple-400">{{ $stats['redis_peak_memory'] ?? 'N/A' }}</div>
        </div>
        <div class="flex-1 p-2 bg-white dark:bg-gray-800 rounded">
            <div class="text-[9px] text-gray-500 dark:text-gray-400">Total</div>
            <div class="text-xs font-semibold">{{ number_format($stats['total_riders'] ?? 0) }}</div>
        </div>
        <div class="flex-1 p-2 bg-white dark:bg-gray-800 rounded">
            <div class="text-[9px] text-gray-500 dark:text-gray-400">Online</div>
            <div class="text-xs font-semibold text-green-600 dark:text-green-400">{{ number_format($stats['online_riders'] ?? 0) }}</div>
        </div>
        <div class="flex-1 p-2 bg-white dark:bg-gray-800 rounded">
            <div class="text-[9px] text-gray-500 dark:text-gray-400">Busy</div>
            <div class="text-xs font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['busy_riders'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Main Content - Force Horizontal with Flex --}}
    <div class="flex gap-3">
        {{-- System Info --}}
        <div class="w-48 flex-shrink-0 p-3 bg-white dark:bg-gray-800 rounded">
            <h3 class="mb-2 text-[10px] font-semibold uppercase">System</h3>
            <div class="space-y-2">
                <div>
                    <div class="text-[9px] text-gray-500 dark:text-gray-400">Host</div>
                    <div class="text-[10px] font-mono">{{ $stats['redis_host'] ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-[9px] text-gray-500 dark:text-gray-400">Port</div>
                    <div class="text-[10px] font-mono">{{ $stats['redis_port'] ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-[9px] text-gray-500 dark:text-gray-400">Refresh</div>
                    <div class="text-[10px]">5s</div>
                </div>
                <div>
                    <div class="text-[9px] text-gray-500 dark:text-gray-400">Updated</div>
                    <div class="text-[10px] font-mono">{{ now()->format('H:i:s') }}</div>
                </div>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <h3 class="mb-2 text-[10px] font-semibold uppercase">Cache Layers</h3>
                <div class="space-y-1.5">
                    @foreach($stats['scopes'] ?? [] as $scope)
                        <div class="p-2 bg-gray-50 dark:bg-gray-900/50 rounded">
                            <div class="flex items-center justify-between">
                                <div class="text-[10px] font-medium">{{ Str::replace(' Cache', '', $scope['name']) }}</div>
                                <div class="text-[9px] text-blue-600 dark:text-blue-400">{{ $scope['ttl'] }}</div>
                            </div>
                            <div class="text-[9px] font-mono text-gray-500">{{ $scope['scope'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Online Riders --}}
        <div class="flex-1 min-w-0 p-3 bg-white dark:bg-gray-800 rounded">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[10px] font-semibold uppercase">Online Riders</h3>
                <span class="text-[9px] text-gray-500">{{ count($onlineRiders) }}</span>
            </div>

            @if(count($onlineRiders) > 0)
                <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded">
                    <div class="overflow-y-auto" style="max-height: 480px;">
                        <table class="w-full text-[10px]">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                <tr>
                                    <th class="px-2 py-1 text-left text-[9px] font-medium text-gray-500">ID</th>
                                    <th class="px-2 py-1 text-left text-[9px] font-medium text-gray-500">Name</th>
                                    <th class="px-2 py-1 text-left text-[9px] font-medium text-gray-500">Location</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($onlineRiders as $rider)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-2 py-1 font-mono">{{ $rider['rider_id'] ?? '-' }}</td>
                                        <td class="px-2 py-1 truncate" style="max-width: 100px;">{{ $rider['name'] ?? '-' }}</td>
                                        <td class="px-2 py-1 font-mono text-[9px] text-gray-600 dark:text-gray-400">
                                            @if(isset($rider['lat']) && isset($rider['lng']))
                                                {{ number_format($rider['lat'], 2) }},{{ number_format($rider['lng'], 2) }}
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
                <div class="py-8 text-center text-[10px] text-gray-500">No online riders</div>
            @endif
        </div>

        {{-- Busy Riders --}}
        <div class="flex-1 min-w-0 p-3 bg-white dark:bg-gray-800 rounded">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[10px] font-semibold uppercase">Busy Riders</h3>
                <span class="text-[9px] text-gray-500">{{ count($busyRiders) }}</span>
            </div>

            @if(count($busyRiders) > 0)
                <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded">
                    <div class="overflow-y-auto" style="max-height: 480px;">
                        <table class="w-full text-[10px]">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                <tr>
                                    <th class="px-2 py-1 text-left text-[9px] font-medium text-gray-500">ID</th>
                                    <th class="px-2 py-1 text-left text-[9px] font-medium text-gray-500">Name</th>
                                    <th class="px-2 py-1 text-left text-[9px] font-medium text-gray-500">Location</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($busyRiders as $rider)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-2 py-1 font-mono">{{ $rider['rider_id'] ?? '-' }}</td>
                                        <td class="px-2 py-1 truncate" style="max-width: 100px;">{{ $rider['name'] ?? '-' }}</td>
                                        <td class="px-2 py-1 font-mono text-[9px] text-gray-600 dark:text-gray-400">
                                            @if(isset($rider['lat']) && isset($rider['lng']))
                                                {{ number_format($rider['lat'], 2) }},{{ number_format($rider['lng'], 2) }}
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
                <div class="py-8 text-center text-[10px] text-gray-500">No busy riders</div>
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
