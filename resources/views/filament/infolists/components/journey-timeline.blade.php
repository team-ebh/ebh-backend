@php
    use App\Enums\Trip\TripStatusEnum;
    use App\Enums\Trip\TripLocationStatusEnum;
    use App\Enums\Trip\TripLocationTypeEnum;
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
        $trip->load([
            'locations:id,trip_id,location_title,location_sub_title,latitude,longitude,type,sequence,status',
            'locations.statusLogs' => fn($query) => $query->orderBy('id', 'desc'),
        ]);
    }
    if (!$trip->relationLoaded('rider')) {
        $trip->load(['rider:id,full_name,phone_number,email,latitude,longitude,last_location_update']);
    }

    $tripStatus = $trip->status;
    $rideType = $trip->ride_type;
    $isOneWay = in_array($rideType, [RideTypeEnum::ONE_WAY, RideTypeEnum::ROUND_TRIP], true);
    $isRoundTripWithWait = $rideType === RideTypeEnum::ROUND_TRIP_WAIT;

    $locations = $trip->locations->sortBy('sequence');
    $originLocation = $locations->where('type', TripLocationTypeEnum::ORIGIN)->first();
    $destinationLocations = $locations->where('type', TripLocationTypeEnum::DESTINATION);
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
        $diffInMinutes = round($diffInMinutes ?: 0);

        if ($diffInMinutes < 1) return null;
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

    $calculateArrivalTime = function($etaString, $baseTime = null) {
        if (!$etaString) return null;

        $baseTime = $baseTime ?? now();
        $minutes = 0;

        if (preg_match('/(\d+)h/', $etaString, $hours)) {
            $minutes += (int)$hours[1] * 60;
        }
        if (preg_match('/(\d+)\s*m/', $etaString, $mins)) {
            $minutes += (int)$mins[1];
        }
        if (preg_match('/^(\d+)\s*min$/', $etaString, $mins)) {
            $minutes += (int)$mins[1];
        }

        if ($minutes > 0) {
            return $baseTime->copy()->addMinutes($minutes);
        }

        return null;
    };

    $isCancelled = in_array($tripStatus, [TripStatusEnum::CANCELED_BY_CUSTOMER, TripStatusEnum::CANCELLED_BY_RIDER], true);

    $steps = [];

    // Step 1: Trip Created
    $steps[] = [
        'title' => trans('trips.admin.timeline.trip_created'),
        'icon' => '📝',
        'timestamp' => $trip->created_at,
        'status' => 'completed',
    ];

    // Get all status logs we need
    $acceptedLog = $findTripStatusLog(TripStatusEnum::ACCEPTED_RIDER);
    $inProgressLog = $findTripStatusLog(TripStatusEnum::IN_PROGRESS);
    $arrivedAtOrigin = $findLocationStatusLog($originLocation, TripLocationStatusEnum::ARRIVED);
    $pickedUpAtOrigin = $findLocationStatusLog($originLocation, TripLocationStatusEnum::PICKED_UP);

    // Step 2: Accepted & En Route to Pickup
    if ($arrivedAtOrigin) {
        // Completed - show duration
        $travelTime = $inProgressLog ? $calculateWaitTime($inProgressLog->created_at, $arrivedAtOrigin->created_at) : null;
        $steps[] = [
            'title' => trans('trips.admin.timeline.en_route_to_pickup'),
            'icon' => '🚗',
            'timestamp' => $acceptedLog?->created_at,
            'status' => 'completed',
            'duration' => $travelTime,
        ];
    } elseif ($acceptedLog || $inProgressLog) {
        // Active - show ETA
        $eta = null;
        if ($trip->rider && $originLocation && $trip->rider->latitude && $trip->rider->longitude && $originLocation->latitude && $originLocation->longitude) {
            $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $originLocation->latitude, $originLocation->longitude);
        }
        $step = [
            'title' => trans('trips.admin.timeline.en_route_to_pickup'),
            'icon' => '🚗',
            'timestamp' => $acceptedLog?->created_at,
            'status' => 'active',
        ];
        if ($eta) {
            $step['eta'] = $eta;
            $step['arrivalTime'] = $calculateArrivalTime($eta, $acceptedLog?->created_at);
        }
        $steps[] = $step;
    } else {
        // Pending
        $steps[] = [
            'title' => trans('trips.admin.timeline.waiting_for_rider'),
            'icon' => '⏳',
            'status' => 'pending',
        ];
    }

    // Step 3: Arrived & Waiting for Passenger
    if ($pickedUpAtOrigin) {
        // Completed - show wait time
        $waitTime = $arrivedAtOrigin ? $calculateWaitTime($arrivedAtOrigin->created_at, $pickedUpAtOrigin->created_at) : null;
        $steps[] = [
            'title' => trans('trips.admin.timeline.rider_arrived'),
            'icon' => '📍',
            'timestamp' => $arrivedAtOrigin?->created_at,
            'status' => 'completed',
            'waitTime' => $waitTime,
        ];
    } elseif ($arrivedAtOrigin) {
        // Active - show live wait time
        $waitTime = $calculateWaitTime($arrivedAtOrigin->created_at);
        $steps[] = [
            'title' => trans('trips.admin.timeline.rider_arrived'),
            'icon' => '📍',
            'timestamp' => $arrivedAtOrigin->created_at,
            'status' => 'active',
            'waitTime' => $waitTime,
            'isLive' => true,
        ];
    } else {
        // Pending
        $steps[] = [
            'title' => trans('trips.admin.timeline.rider_arrived'),
            'icon' => '📍',
            'status' => 'pending',
        ];
    }

    // For One-Way & Round Trip (without wait)
    if ($isOneWay) {
        $droppedAtDestination = $findLocationStatusLog($finalDestination, TripLocationStatusEnum::DROPPED_OFF);

        // Step 4: Picked Up & En Route to Destination
        if ($droppedAtDestination) {
            // Completed - show duration
            $travelTime = $pickedUpAtOrigin ? $calculateWaitTime($pickedUpAtOrigin->created_at, $droppedAtDestination->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.en_route_to_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUpAtOrigin?->created_at,
                'status' => 'completed',
                'duration' => $travelTime,
            ];
        } elseif ($pickedUpAtOrigin) {
            // Active - show ETA
            $eta = null;
            if ($trip->rider && $finalDestination && $trip->rider->latitude && $trip->rider->longitude && $finalDestination->latitude && $finalDestination->longitude) {
                $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $finalDestination->latitude, $finalDestination->longitude);
            }
            $step = [
                'title' => trans('trips.admin.timeline.en_route_to_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUpAtOrigin->created_at,
                'status' => 'active',
            ];
            if ($eta) {
                $step['eta'] = $eta;
                $step['arrivalTime'] = $calculateArrivalTime($eta, $pickedUpAtOrigin->created_at);
            }
            $steps[] = $step;
        } else {
            // Pending
            $steps[] = [
                'title' => trans('trips.admin.timeline.passenger_pickup'),
                'icon' => '👤',
                'status' => 'pending',
            ];
        }

        // Step 5: Complete
        if ($tripStatus === TripStatusEnum::COMPLETED) {
            $completedLog = $findTripStatusLog(TripStatusEnum::COMPLETED);
            $steps[] = [
                'title' => trans('trips.admin.timeline.trip_completed'),
                'icon' => '✅',
                'timestamp' => $completedLog?->created_at,
                'status' => 'completed',
            ];
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.trip_completed'),
                'icon' => '✅',
                'status' => 'pending',
            ];
        }
    }

    // For Round Trip with Wait
    if ($isRoundTripWithWait) {
        $droppedAtFirst = $findLocationStatusLog($firstDestination, TripLocationStatusEnum::DROPPED_OFF);
        // After pickup at first destination, the FINAL destination gets PICKED_UP status (not the first destination)
        $pickedUpAtFinal = $findLocationStatusLog($finalDestination, TripLocationStatusEnum::PICKED_UP);
        $completedAtFinal = $findLocationStatusLog($finalDestination, TripLocationStatusEnum::COMPLETED);

        // Step 4: Picked Up & En Route to First Destination
        if ($droppedAtFirst) {
            $travelTime = $pickedUpAtOrigin ? $calculateWaitTime($pickedUpAtOrigin->created_at, $droppedAtFirst->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.en_route_to_first_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUpAtOrigin?->created_at,
                'status' => 'completed',
                'duration' => $travelTime,
            ];
        } elseif ($pickedUpAtOrigin) {
            $eta = null;
            if ($trip->rider && $firstDestination && $trip->rider->latitude && $trip->rider->longitude && $firstDestination->latitude && $firstDestination->longitude) {
                $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $firstDestination->latitude, $firstDestination->longitude);
            }
            $step = [
                'title' => trans('trips.admin.timeline.en_route_to_first_destination'),
                'icon' => '👤',
                'timestamp' => $pickedUpAtOrigin->created_at,
                'status' => 'active',
            ];
            if ($eta) {
                $step['eta'] = $eta;
                $step['arrivalTime'] = $calculateArrivalTime($eta, $pickedUpAtOrigin->created_at);
            }
            $steps[] = $step;
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.passenger_pickup'),
                'icon' => '👤',
                'status' => 'pending',
            ];
        }

        // Step 5: Dropped & Waiting for Pickup Again
        if ($pickedUpAtFinal) {
            // Customer was picked up again (final destination has PICKED_UP status)
            $waitTime = $droppedAtFirst ? $calculateWaitTime($droppedAtFirst->created_at, $pickedUpAtFinal->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.passenger_dropped_off'),
                'icon' => '⏱️',
                'timestamp' => $droppedAtFirst?->created_at,
                'status' => 'completed',
                'waitTime' => $waitTime,
            ];
        } elseif ($droppedAtFirst) {
            // Customer is waiting at first destination
            $waitTime = $calculateWaitTime($droppedAtFirst->created_at);
            $steps[] = [
                'title' => trans('trips.admin.timeline.passenger_dropped_off'),
                'icon' => '⏱️',
                'timestamp' => $droppedAtFirst->created_at,
                'status' => 'active',
                'waitTime' => $waitTime,
                'isLive' => true,
            ];
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.drop_off'),
                'icon' => '⏱️',
                'status' => 'pending',
            ];
        }

        // Step 6: Picked Up Again & En Route to Final Destination
        if ($completedAtFinal) {
            // Trip is completed - final destination was reached
            $travelTime = $pickedUpAtFinal ? $calculateWaitTime($pickedUpAtFinal->created_at, $completedAtFinal->created_at) : null;
            $steps[] = [
                'title' => trans('trips.admin.timeline.en_route_to_final_destination'),
                'icon' => '🔄',
                'timestamp' => $pickedUpAtFinal?->created_at,
                'status' => 'completed',
                'duration' => $travelTime,
            ];
        } elseif ($pickedUpAtFinal) {
            // Customer picked up, on the way to final destination
            $eta = null;
            if ($trip->rider && $finalDestination && $trip->rider->latitude && $trip->rider->longitude && $finalDestination->latitude && $finalDestination->longitude) {
                $eta = $calculateETA($trip->rider->latitude, $trip->rider->longitude, $finalDestination->latitude, $finalDestination->longitude);
            }
            $step = [
                'title' => trans('trips.admin.timeline.en_route_to_final_destination'),
                'icon' => '🔄',
                'timestamp' => $pickedUpAtFinal->created_at,
                'status' => 'active',
            ];
            if ($eta) {
                $step['eta'] = $eta;
                $step['arrivalTime'] = $calculateArrivalTime($eta, $pickedUpAtFinal->created_at);
            }
            $steps[] = $step;
        } else {
            // Waiting for second pickup
            $steps[] = [
                'title' => trans('trips.admin.timeline.second_pickup'),
                'icon' => '🔄',
                'status' => 'pending',
            ];
        }

        // Step 7: Complete
        if ($tripStatus === TripStatusEnum::COMPLETED) {
            $completedLog = $findTripStatusLog(TripStatusEnum::COMPLETED);
            $steps[] = [
                'title' => trans('trips.admin.timeline.trip_completed'),
                'icon' => '✅',
                'timestamp' => $completedLog?->created_at,
                'status' => 'completed',
            ];
        } else {
            $steps[] = [
                'title' => trans('trips.admin.timeline.trip_completed'),
                'icon' => '✅',
                'status' => 'pending',
            ];
        }
    }

    // If cancelled, add cancellation step
    if ($isCancelled) {
        $cancelledLog = $findTripStatusLog($tripStatus);
        $steps[] = [
            'title' => trans('trips.admin.timeline.trip_cancelled'),
            'icon' => '❌',
            'timestamp' => $cancelledLog?->created_at,
            'status' => 'cancelled',
        ];
    }
@endphp

{{-- HORIZONTAL TIMELINE --}}
<div class="overflow-x-auto rounded-lg bg-white p-8 dark:bg-gray-800" style="line-height: 1.8;">
    <table class="w-full" style="table-layout: fixed;">
        <tr>
            @foreach ($steps as $index => $step)
                @php
                    $isCompleted = $step['status'] === 'completed';
                    $isActive = $step['status'] === 'active';
                    $isPending = $step['status'] === 'pending';
                    $isCancelled = $step['status'] === 'cancelled';
                    $isLast = $index === count($steps) - 1;
                    $isLive = $step['isLive'] ?? false;
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

                        {{-- Title --}}
                        <h4 class="mb-1 text-center text-xs font-bold
                            {{ $isActive ? 'text-blue-900 dark:text-blue-100' : '' }}
                            {{ $isCompleted ? 'text-gray-900 dark:text-gray-100' : '' }}
                            {{ $isPending ? 'text-gray-500 dark:text-gray-400' : '' }}
                            {{ $isCancelled ? 'text-red-900 dark:text-red-100' : '' }}">
                            {{ $step['title'] }}
                        </h4>

                        {{-- Time or Pending --}}
                        @if (isset($step['timestamp']))
                            <div class="mt-2 text-center text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $step['timestamp']->format('g:i A') }} <span
                                    class="text-gray-500">{{ $step['timestamp']->format('M d, Y') }}</span>
                            </div>
                        @else
                            <div class="mt-2 text-center text-xs font-semibold text-gray-400 dark:text-gray-500">
                                Pending
                            </div>
                        @endif
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
</div>
