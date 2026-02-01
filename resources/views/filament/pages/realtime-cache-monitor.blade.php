<x-filament-panels::page>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Rider Locations</p>
                <p class="text-3xl font-bold text-primary-600">{{ $stats['locations']['count'] ?? 0 }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Online Riders</p>
                <p class="text-3xl font-bold text-success-600">{{ $stats['status']['online'] ?? 0 }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Ready Riders</p>
                <p class="text-3xl font-bold text-info-600">{{ $stats['status']['ready'] ?? 0 }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Active Trips</p>
                <p class="text-3xl font-bold text-warning-600">{{ $stats['trips']['count'] ?? 0 }}</p>
            </div>
        </x-filament::section>
    </div>

    {{-- Health Status --}}
    <x-filament::section class="mb-6">
        <x-slot name="heading">System Status</x-slot>

        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Storage:</span>
                @if($healthStatus['healthy'] ?? false)
                    <x-filament::badge color="success">Redis (Primary)</x-filament::badge>
                @else
                    <x-filament::badge color="warning">Database (Fallback)</x-filament::badge>
                @endif
            </div>

            @if($healthStatus['in_cooldown'] ?? false)
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Redis Retry In:</span>
                    <span class="font-medium text-warning-600">{{ $healthStatus['cooldown_remaining'] ?? 0 }}s</span>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- Redis Info --}}
    <x-filament::section>
        <x-slot name="heading">Redis Server Info</x-slot>

        @if(isset($redisInfo['error']))
            <p class="text-danger-500">{{ $redisInfo['error'] }}</p>
        @else
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Version</p>
                    <p class="font-medium">{{ $redisInfo['version'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Memory Used</p>
                    <p class="font-medium">{{ $redisInfo['used_memory'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Connected Clients</p>
                    <p class="font-medium">{{ $redisInfo['connected_clients'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Uptime (days)</p>
                    <p class="font-medium">{{ $redisInfo['uptime_days'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">RTC Keys</p>
                    <p class="font-medium">{{ $redisInfo['total_keys'] ?? 0 }}</p>
                </div>
            </div>
        @endif
    </x-filament::section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Rider Locations --}}
        <x-filament::section>
            <x-slot name="heading">Rider Locations ({{ count($riderLocations) }})</x-slot>

            <div class="overflow-x-auto max-h-80">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Rider ID</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Latitude</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Longitude</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($riderLocations as $location)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2 font-medium">{{ $location['rider_id'] }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ number_format($location['lat'], 6) }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ number_format($location['lng'], 6) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No rider locations cached</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Rider Statuses --}}
        <x-filament::section>
            <x-slot name="heading">Rider Statuses ({{ count($riderStatuses) }})</x-slot>

            <div class="overflow-x-auto max-h-80">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Rider ID</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($riderStatuses as $status)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2 font-medium">{{ $status['rider_id'] }}</td>
                                <td class="px-4 py-2">
                                    @if($status['status'] === 'online')
                                        <x-filament::badge color="success">Online</x-filament::badge>
                                    @elseif($status['status'] === 'busy')
                                        <x-filament::badge color="warning">Busy</x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray">{{ $status['status'] }}</x-filament::badge>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $status['last_seen'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No rider statuses cached</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    {{-- Active Trips --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Active Trips ({{ count($activeTrips) }})</x-slot>

        <div class="overflow-x-auto max-h-80">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Trip ID</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Rider ID</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Customer ID</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Route Points</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($activeTrips as $trip)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2 font-medium">{{ $trip['trip_id'] }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'accepted' => 'info',
                                        'arrived' => 'info',
                                        'picked_up' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                    $color = $statusColors[$trip['status']] ?? 'gray';
                                @endphp
                                <x-filament::badge :color="$color">{{ ucfirst($trip['status']) }}</x-filament::badge>
                            </td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $trip['rider_id'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $trip['customer_id'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $trip['route_points'] }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $trip['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No active trips cached</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
