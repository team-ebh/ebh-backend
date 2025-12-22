@php
    use App\Enums\Trip\TripStatusEnum;
    use App\Enums\Trip\TripLocationStatusEnum;
    use App\Enums\Trip\RideTypeEnum;
    use Carbon\Carbon;

    $trip = $getState()['trip'] ?? null;

    if (!$trip) {
        echo '<div class="p-6 text-center text-gray-500">No trip data available</div>';
        return;
    }

    // Ensure relationships are loaded (they should already be loaded from ViewTrip.php)
    if (!$trip->relationLoaded('statusLogs')) {
        $trip->load(['statusLogs' => fn($query) => $query->orderBy('id', 'desc')]);
    }
    if (!$trip->relationLoaded('locations')) {
        $trip->load(['locations:id,trip_id,location_title,location_sub_title,latitude,longitude,type,sequence,status']);
    }
    if (!$trip->relationLoaded('rider')) {
        $trip->load(['rider:id,full_name,latitude,longitude']);
    }
    // Load location status logs separately if not already loaded
    foreach ($trip->locations as $location) {
        if (!$location->relationLoaded('statusLogs')) {
            $location->load(['statusLogs' => fn($query) => $query->orderBy('id', 'desc')]);
        }
    }

    $tripStatus = $trip->status;
    $rideType = $trip->ride_type;
    $isOneWay = in_array($rideType, [RideTypeEnum::ONE_WAY, RideTypeEnum::ROUND_TRIP], true);
    $isRoundTripWithWait = $rideType === RideTypeEnum::ROUND_TRIP_WAIT;
    $isDemandTrip = $rideType === RideTypeEnum::ROUND_TRIP && $trip->demand_trip_id;

    $locations = $trip->locations->sortBy('sequence');
    $originLocation = $locations->firstWhere('type', 'origin');
    $destinationLocations = $locations->where('type', 'destination');
    $firstDestination = $destinationLocations->first();
    $finalDestination = $destinationLocations->last();

    $findTripStatusLog = function($status) use ($trip) {
        return $trip->statusLogs->first(fn($log) => $log->status === $status);
    };

    $findLocationStatusLog = function($location, $status) {
        if (!$location) return null;
        return $location->statusLogs->first(fn($log) => $log->status === $status);
    };

    $calculateWaitTime = function($startTime, $endTime = null) {
        if (!$startTime) return null;
        $end = $endTime ?? now();
        $diffInMinutes = $startTime->diffInMinutes($end);

        if ($diffInMinutes < 1) return '< 1 min';
        if ($diffInMinutes < 60) return $diffInMinutes . ' min';

        $hours = floor($diffInMinutes / 60);
        $mins = $diffInMinutes % 60;
        return $hours . 'h ' . $mins . 'm';
    };

    $calculateETA = function($fromLat, $fromLng, $toLat, $toLng) {
        if (!$fromLat || !$fromLng || !$toLat || !$toLng) return null;

        $earthRadius = 6371;
        $latFrom = deg2rad($fromLat);
        $lonFrom = deg2rad($fromLng);
        $latTo = deg2rad($toLat);
        $lonTo = deg2rad($toLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        $averageSpeed = 40;
        $timeInMinutes = ($distance / $averageSpeed) * 60;

        if ($timeInMinutes < 1) return '< 1 min';
        if ($timeInMinutes < 60) return round($timeInMinutes) . ' min';

        $hours = floor($timeInMinutes / 60);
        $mins = round($timeInMinutes % 60);
        return $hours . 'h ' . $mins . 'm';
    };

    $isCancelled = in_array($tripStatus, [TripStatusEnum::CANCELED_BY_CUSTOMER, TripStatusEnum::CANCELLED_BY_RIDER], true);

    $steps = [];

    $steps[] = [
        'title' => trans('trips.admin.timeline.trip_created'),
        'icon' => '📝',
        'timestamp' => $trip->created_at,
        'status' => 'completed',
    ];

    $acceptedLog = $findTripStatusLog(TripStatusEnum::ACCEPTED_RIDER);
    $inProgressLog = $findTripStatusLog(TripStatusEnum::IN_PROGRESS);
    $arrivedAtPickup = $findLocationStatusLog($originLocation, TripLocationStatusEnum::ARRIVED);

    if ($arrivedAtPickup) {
        $travelTime = ($inProgressLog && $arrivedAtPickup) ? $calculateWaitTime($inProgressLog->created_at, $arrivedAtPickup->created_at) : null;
        $steps[] = [
            'title' => trans('trips.admin.timeline.en_route_to_pickup'),
            'icon' => '🚗',
            'timestamp' => $inProgressLog?->created_at,
            'status' => 'completed',
            'duration' => $travelTime,
        ];
    } elseif ($acceptedLog || $inProgressLog) {
        $eta = null;
        if ($trip->rider && $originLocation && $trip->rider->latitude && $trip->rider->longitude && $originLocation->latitude && $originLocation->longitude) {
            $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $originLocation->latitude, $originLocation->longitude);
        }
        $step = [
            'title' => trans('trips.admin.timeline.en_route_to_pickup'),
            'icon' => '🚗',
            'timestamp' => $inProgressLog?->created_at ?? $acceptedLog?->created_at,
            'status' => 'active',
        ];
        if ($eta) {
            $step['eta'] = $eta;
        }
        $steps[] = $step;
    } else {
        $steps[] = [
            'title' => trans('trips.admin.timeline.waiting_for_rider'),
            'icon' => '⏳',
            'status' => 'pending',
        ];
    }

    $pickedUp = $findLocationStatusLog($originLocation, TripLocationStatusEnum::PICKED_UP);

    if ($pickedUp) {
        $waitTime = ($arrivedAtPickup && $pickedUp) ? $calculateWaitTime($arrivedAtPickup->created_at, $pickedUp->created_at) : null;
        $steps[] = [
            'title' => trans('trips.admin.timeline.rider_arrived'),
            'icon' => '📍',
            'timestamp' => $arrivedAtPickup?->created_at,
            'status' => 'completed',
            'waitTime' => $waitTime,
        ];
    } elseif ($arrivedAtPickup) {
        $steps[] = [
            'title' => trans('trips.admin.timeline.rider_arrived'),
            'icon' => '📍',
            'timestamp' => $arrivedAtPickup->created_at,
            'status' => 'active',
            'waitTime' => $calculateWaitTime($arrivedAtPickup->created_at),
            'isLive' => true,
        ];
    } else {
        $steps[] = [
            'title' => trans('trips.admin.timeline.rider_arrived'),
            'icon' => '📍',
            'status' => 'pending',
        ];
    }

    if ($isOneWay) {
        $droppedOff = $findLocationStatusLog($finalDestination, TripLocationStatusEnum::DROPPED_OFF);
        $arrivedAtDestination = $findLocationStatusLog($finalDestination, TripLocationStatusEnum::ARRIVED);

        if ($droppedOff) {
            $travelTime = ($pickedUp && $arrivedAtDestination) ? $calculateWaitTime($pickedUp->created_at, $arrivedAtDestination->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.en_route_to_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUp?->created_at,
                'status' => 'completed',
                'duration' => $travelTime,
            ];
        } elseif ($pickedUp) {
            $eta = null;
            if ($trip->rider && $finalDestination && $trip->rider->latitude && $trip->rider->longitude && $finalDestination->latitude && $finalDestination->longitude) {
                $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $finalDestination->latitude, $finalDestination->longitude);
            }
            $step = [
                'title' => trans('trips.admin.timeline.en_route_to_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUp->created_at,
                'status' => 'active',
            ];
            if ($eta) {
                $step['eta'] = $eta;
            }
            $steps[] = $step;
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.passenger_pickup'),
                'icon' => '👤',
                'status' => 'pending',
            ];
        }
    } elseif ($isRoundTripWithWait) {
        $firstDroppedOff = $findLocationStatusLog($firstDestination, TripLocationStatusEnum::DROPPED_OFF);
        $firstArrived = $findLocationStatusLog($firstDestination, TripLocationStatusEnum::ARRIVED);
        $secondPickup = $findLocationStatusLog($firstDestination, TripLocationStatusEnum::PICKED_UP);
        $finalDroppedOff = $findLocationStatusLog($finalDestination, TripLocationStatusEnum::DROPPED_OFF);
        $finalArrived = $findLocationStatusLog($finalDestination, TripLocationStatusEnum::ARRIVED);

        if ($firstDroppedOff || $firstArrived) {
            $travelTime = ($pickedUp && $firstArrived) ? $calculateWaitTime($pickedUp->created_at, $firstArrived->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.en_route_to_first_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUp?->created_at,
                'status' => 'completed',
                'duration' => $travelTime,
            ];
        } elseif ($pickedUp) {
            $eta = null;
            if ($trip->rider && $firstDestination && $trip->rider->latitude && $trip->rider->longitude && $firstDestination->latitude && $firstDestination->longitude) {
                $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $firstDestination->latitude, $firstDestination->longitude);
            }
            $step = [
                'title' => trans('trips.admin.timeline.en_route_to_first_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUp->created_at,
                'status' => 'active',
            ];
            if ($eta) {
                $step['eta'] = $eta;
            }
            $steps[] = $step;
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.passenger_pickup'),
                'icon' => '👤',
                'status' => 'pending',
            ];
        }

        if ($secondPickup) {
            $waitTime = ($firstDroppedOff && $secondPickup) ? $calculateWaitTime($firstDroppedOff->created_at, $secondPickup->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.waiting_for_pickup_again'),
                'icon' => '⏱️',
                'timestamp' => $firstDroppedOff?->created_at,
                'status' => 'completed',
                'waitTime' => $waitTime,
            ];
        } elseif ($firstDroppedOff) {
            $steps[] = [
                'title' => trans('trips.admin.timeline.waiting_for_pickup_again'),
                'icon' => '⏱️',
                'timestamp' => $firstDroppedOff->created_at,
                'status' => 'active',
                'waitTime' => $calculateWaitTime($firstDroppedOff->created_at),
                'isLive' => true,
            ];
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.drop_off'),
                'icon' => '⏱️',
                'status' => 'pending',
            ];
        }

        if ($finalDroppedOff || $finalArrived) {
            $travelTime = ($secondPickup && $finalArrived) ? $calculateWaitTime($secondPickup->created_at, $finalArrived->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.en_route_to_final_destination'),
                'icon' => '🔄',
                'timestamp' => $secondPickup?->created_at,
                'status' => 'completed',
                'duration' => $travelTime,
            ];
        } elseif ($secondPickup) {
            $eta = null;
            if ($trip->rider && $finalDestination && $trip->rider->latitude && $trip->rider->longitude && $finalDestination->latitude && $finalDestination->longitude) {
                $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $finalDestination->latitude, $finalDestination->longitude);
            }
            $step = [
                'title' => trans('trips.admin.timeline.en_route_to_final_destination'),
                'icon' => '🔄',
                'timestamp' => $secondPickup->created_at,
                'status' => 'active',
            ];
            if ($eta) {
                $step['eta'] = $eta;
            }
            $steps[] = $step;
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.second_pickup'),
                'icon' => '🔄',
                'status' => 'pending',
            ];
        }
    }

    $completedLog = $findTripStatusLog(TripStatusEnum::COMPLETED);

    if ($isCancelled) {
        $cancelLog = $findTripStatusLog($tripStatus);
        $steps[] = [
            'title' => trans('trips.admin.timeline.trip_cancelled'),
            'icon' => '❌',
            'timestamp' => $cancelLog?->created_at,
            'status' => 'cancelled',
        ];
    } elseif ($completedLog) {
        $steps[] = [
            'title' => trans('trips.admin.timeline.trip_completed'),
            'icon' => '🏁',
            'timestamp' => $completedLog->created_at,
            'status' => 'completed',
        ];
    } else {
        $steps[] = [
            'title' => trans('trips.admin.timeline.trip_completed'),
            'icon' => '🏁',
            'status' => 'pending',
        ];
    }
@endphp

<div class="space-y-6">
    @if ($isDemandTrip)
        <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-700 dark:bg-purple-900/20">
            <div class="flex items-start gap-3">
                <div class="text-2xl">🔗</div>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-purple-900 dark:text-purple-100">
                        {{ trans('trips.admin.fields.demand_trip') }}
                    </h4>
                    <p class="mt-1 text-xs text-purple-700 dark:text-purple-300">
                        {{ trans('trips.admin.fields.linked_to_trip') }}:
                        <a href="{{ route('filament.admin.resources.trips.view', $trip->demand_trip_id) }}"
                           class="font-semibold underline hover:no-underline" target="_blank">
                            #{{ $trip->demand_trip_id }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- SIMPLE HORIZONTAL TIMELINE --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white p-8 dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full" style="table-layout: fixed;">
            <tr>
                @foreach ($steps as $index => $step)
                    @php
                        $isCompleted = $step['status'] === 'completed';
                        $isActive = $step['status'] === 'active';
                        $isPending = $step['status'] === 'pending';
                        $isCancelled = $step['status'] === 'cancelled';
                        $isLive = $step['isLive'] ?? false;
                        $isLast = $loop->last;
                    @endphp
                    <td class="relative align-top" style="width: {{ 100 / count($steps) }}%;">
                        <div class="flex flex-col items-center px-2">
                            {{-- Icon --}}
                            <div class="relative z-10 mb-2 flex h-10 w-10 shrink-0 items-center justify-center rounded-full shadow-lg
                                {{ $isCompleted ? 'bg-gradient-to-br from-green-500 to-green-600' : '' }}
                                {{ $isActive ? 'bg-gradient-to-br from-blue-500 to-blue-600 animate-pulse' : '' }}
                                {{ $isPending ? 'bg-gray-300 dark:bg-gray-600' : '' }}
                                {{ $isCancelled ? 'bg-gradient-to-br from-red-500 to-red-600' : '' }}">
                                <span class="text-lg">{{ $step['icon'] }}</span>
                            </div>

                            {{-- Title --}}
                            <h4 class="mb-1 text-center text-xs font-bold
                                {{ $isActive ? 'text-blue-900 dark:text-blue-100' : '' }}
                                {{ $isCompleted ? 'text-gray-900 dark:text-gray-100' : '' }}
                                {{ $isPending ? 'text-gray-500 dark:text-gray-400' : '' }}
                                {{ $isCancelled ? 'text-red-900 dark:text-red-100' : '' }}">
                                {{ $step['title'] }}
                            </h4>

                            {{-- Horizontal Line --}}
                            @if (!$isLast)
                                <div class="absolute left-1/2 top-5 h-0.5 w-full bg-gray-300 dark:bg-gray-600"
                                     style="z-index: 1;">
                                    @if ($isCompleted)
                                        <div class="h-full w-full bg-green-500"></div>
                                    @elseif ($isActive)
                                        <div class="h-full w-1/2 animate-pulse bg-blue-500"></div>
                                    @endif
                                </div>
                            @endif

                            {{-- Time --}}
                            @if (isset($step['timestamp']))
                                <div class="mt-2 text-center text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $step['timestamp']->format('g:i A') }} <span class="text-gray-500">{{ $step['timestamp']->format('M d, Y') }}</span>
                                </div>
                            @endif

                            {{-- ETA --}}
                            @if (isset($step['eta']))
                                <div class="mt-2 rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">
                                    ⏱️ {{ $step['eta'] }}
                                </div>
                            @endif

                            {{-- Duration --}}
                            @if (isset($step['duration']))
                                <div class="mt-2 rounded bg-green-50 px-2 py-1 text-xs font-semibold text-green-700">
                                    ✓ {{ $step['duration'] }}
                                </div>
                            @endif

                            {{-- Wait --}}
                            @if (isset($step['waitTime']))
                                <div class="mt-2 rounded bg-orange-50 px-2 py-1 text-xs font-semibold text-orange-700 {{ $isLive ? 'animate-pulse' : '' }}">
                                    ⏱️ {{ $step['waitTime'] }}
                                </div>
                            @endif

                            @if ($isPending && !isset($step['timestamp']))
                                <div class="mt-2 text-xs italic text-gray-400">Pending</div>
                            @endif
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- Locations --}}
    @if ($originLocation || $destinationLocations->isNotEmpty())
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
            <h4 class="mb-3 text-sm font-bold text-gray-900 dark:text-gray-100">
                {{ trans('trips.admin.timeline.route_details') }}
            </h4>
            <div class="space-y-2">
                @if ($originLocation)
                    <div class="flex items-start gap-2">
                        <span class="text-green-500">📍</span>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ trans('trips.admin.timeline.pickup_location') }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $originLocation->location_sub_title ?: $originLocation->location_title }}
                            </div>
                        </div>
                    </div>
                @endif

                @foreach ($destinationLocations as $index => $destination)
                    <div class="flex items-start gap-2">
                        <span class="text-red-500">📍</span>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $destinationLocations->count() > 1
                                    ? trans('trips.admin.timeline.destination') . ' ' . ($index + 1)
                                    : trans('trips.admin.timeline.destination') }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $destination->location_sub_title ?: $destination->location_title }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
