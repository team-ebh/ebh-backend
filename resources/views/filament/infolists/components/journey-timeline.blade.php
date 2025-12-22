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

    // Current trip status
    $tripStatus = $trip->status;
    $isActiveTrip = in_array($tripStatus, [
        TripStatusEnum::PENDING_RIDER,
        TripStatusEnum::ACCEPTED_RIDER,
        TripStatusEnum::IN_PROGRESS,
    ], true);

    // Rider information
    $hasRider = $trip->rider !== null;
    $rider = $trip->rider;

    // Ride Type
    $rideType = $trip->ride_type;
    $isDemandTrip = $rideType === RideTypeEnum::ROUND_TRIP && $trip->demand_trip_id;
    $isWithWait = $rideType === RideTypeEnum::ROUND_TRIP_WAIT;

    // Locations
    $locations = $trip->locations->sortBy('sequence');

    // Build progressive timeline steps
    $timelineSteps = [];

    // Helper function to find status log
    $findTripStatusLog = function($status) use ($trip) {
        return $trip->statusLogs->first(fn($log) => $log->status === $status);
    };

    $findLocationStatusLog = function($location, $status) {
        return $location->statusLogs->first(fn($log) => $log->status === $status);
    };

    // Step 1: Trip Created
    $createdLog = $findTripStatusLog(TripStatusEnum::DRAFT);
    $timelineSteps[] = [
        'title' => 'Trip Created',
        'icon' => '📝',
        'timestamp' => $trip->created_at,
        'status' => 'completed',
    ];

    // Step 2: Trip Assigned to Rider
    $acceptedLog = $findTripStatusLog(TripStatusEnum::ACCEPTED_RIDER);
    if ($acceptedLog) {
        $timelineSteps[] = [
            'title' => 'Trip Assigned to Driver',
            'icon' => '✅',
            'timestamp' => $acceptedLog->created_at,
            'status' => 'completed',
            'isCurrent' => $tripStatus === TripStatusEnum::ACCEPTED_RIDER && !$locations->first()?->statusLogs->isNotEmpty(),
        ];
    } elseif ($tripStatus->value >= TripStatusEnum::ACCEPTED_RIDER->value) {
        $timelineSteps[] = [
            'title' => 'Trip Assigned to Driver',
            'icon' => '✅',
            'timestamp' => null,
            'status' => 'current',
            'isCurrent' => true,
        ];
    } else {
        $timelineSteps[] = [
            'title' => 'Waiting for Driver',
            'icon' => '⏳',
            'timestamp' => null,
            'status' => 'pending',
        ];
    }

    // Step 3+: Process each location
    foreach ($locations as $index => $location) {
        $isOrigin = $location->isOrigin();
        $isDestination = $location->isDestination();
        $locationTitle = $location->location_title ?: ($isOrigin ? 'Pickup Location' : 'Destination');

        // Rider en route to location
        if ($index === 0) {
            $inProgressLog = $findTripStatusLog(TripStatusEnum::IN_PROGRESS);
            $arrivedLog = $findLocationStatusLog($location, TripLocationStatusEnum::ARRIVED);

            if ($arrivedLog) {
                $timelineSteps[] = [
                    'title' => 'Driver En Route to Pickup',
                    'icon' => '🚗',
                    'timestamp' => $inProgressLog?->created_at,
                    'status' => 'completed',
                ];
            } elseif ($inProgressLog) {
                $timelineSteps[] = [
                    'title' => 'Driver En Route to Pickup',
                    'icon' => '🚗',
                    'timestamp' => null,
                    'status' => 'current',
                    'isCurrent' => true,
                ];
            } else {
                $timelineSteps[] = [
                    'title' => 'Driver En Route to Pickup',
                    'icon' => '🚗',
                    'timestamp' => null,
                    'status' => 'pending',
                ];
            }
        }

        // Arrived at location
        $arrivedLog = $findLocationStatusLog($location, TripLocationStatusEnum::ARRIVED);
        if ($arrivedLog) {
            $timelineSteps[] = [
                'title' => 'Driver Arrived at ' . $locationTitle,
                'icon' => '📍',
                'timestamp' => $arrivedLog->created_at,
                'status' => 'completed',
                'location' => $location,
            ];
        } elseif ($location->status->value >= TripLocationStatusEnum::ARRIVED->value) {
            $timelineSteps[] = [
                'title' => 'Driver Arrived at ' . $locationTitle,
                'icon' => '📍',
                'timestamp' => null,
                'status' => 'current',
                'isCurrent' => true,
                'location' => $location,
            ];
        } elseif ($tripStatus === TripStatusEnum::IN_PROGRESS) {
            $timelineSteps[] = [
                'title' => 'Driver Arrived at ' . $locationTitle,
                'icon' => '📍',
                'timestamp' => null,
                'status' => 'pending',
                'location' => $location,
            ];
        }

        // Picked up (only for origin)
        if ($isOrigin) {
            $pickedUpLog = $findLocationStatusLog($location, TripLocationStatusEnum::PICKED_UP);
            if ($pickedUpLog) {
                $timelineSteps[] = [
                    'title' => 'Customer Picked Up',
                    'icon' => '👤',
                    'timestamp' => $pickedUpLog->created_at,
                    'status' => 'completed',
                    'location' => $location,
                ];
            } elseif ($location->status->value >= TripLocationStatusEnum::PICKED_UP->value) {
                $timelineSteps[] = [
                    'title' => 'Customer Picked Up',
                    'icon' => '👤',
                    'timestamp' => null,
                    'status' => 'current',
                    'isCurrent' => true,
                    'location' => $location,
                ];
            } elseif ($location->status->value >= TripLocationStatusEnum::ARRIVED->value) {
                $timelineSteps[] = [
                    'title' => 'Customer Pickup',
                    'icon' => '👤',
                    'timestamp' => null,
                    'status' => 'pending',
                    'location' => $location,
                ];
            }
        }

        // En route to next location or destination
        if (!$isDestination || ($isDestination && $locations->count() > $index + 1)) {
            $nextLocation = $locations[$index + 1] ?? null;
            $pickedUpLog = $findLocationStatusLog($location, TripLocationStatusEnum::PICKED_UP);
            $nextArrivedLog = $nextLocation ? $findLocationStatusLog($nextLocation, TripLocationStatusEnum::ARRIVED) : null;

            if ($nextArrivedLog) {
                $timelineSteps[] = [
                    'title' => 'En Route to ' . ($nextLocation->location_title ?: 'Destination'),
                    'icon' => '🚗',
                    'timestamp' => $pickedUpLog?->created_at,
                    'status' => 'completed',
                ];
            } elseif ($pickedUpLog) {
                $timelineSteps[] = [
                    'title' => 'En Route to ' . ($nextLocation->location_title ?: 'Destination'),
                    'icon' => '🚗',
                    'timestamp' => null,
                    'status' => 'current',
                    'isCurrent' => true,
                ];
            } elseif ($location->status->value >= TripLocationStatusEnum::PICKED_UP->value) {
                $timelineSteps[] = [
                    'title' => 'En Route to ' . ($nextLocation->location_title ?: 'Destination'),
                    'icon' => '🚗',
                    'timestamp' => null,
                    'status' => 'pending',
                ];
            }
        }

        // Dropped off (only for destination)
        if ($isDestination) {
            $droppedOffLog = $findLocationStatusLog($location, TripLocationStatusEnum::DROPPED_OFF);
            if ($droppedOffLog) {
                $timelineSteps[] = [
                    'title' => 'Customer Dropped Off at ' . $locationTitle,
                    'icon' => '🎯',
                    'timestamp' => $droppedOffLog->created_at,
                    'status' => 'completed',
                    'location' => $location,
                ];
            } elseif ($location->status->value >= TripLocationStatusEnum::DROPPED_OFF->value) {
                $timelineSteps[] = [
                    'title' => 'Customer Drop-off',
                    'icon' => '🎯',
                    'timestamp' => null,
                    'status' => 'current',
                    'isCurrent' => true,
                    'location' => $location,
                ];
            } elseif ($location->status->value >= TripLocationStatusEnum::ARRIVED->value) {
                $timelineSteps[] = [
                    'title' => 'Customer Drop-off',
                    'icon' => '🎯',
                    'timestamp' => null,
                    'status' => 'pending',
                    'location' => $location,
                ];
            }

            // Waiting (for WITH_WAIT at first destination)
            if ($isWithWait && $location->sequence === 1) {
                $droppedOffLog = $findLocationStatusLog($location, TripLocationStatusEnum::DROPPED_OFF);
                if ($droppedOffLog) {
                    $timelineSteps[] = [
                        'title' => 'Driver Waiting at ' . $locationTitle,
                        'icon' => '⏱️',
                        'timestamp' => $droppedOffLog->created_at,
                        'status' => 'completed',
                        'isWait' => true,
                    ];
                }
            }
        }
    }

    // Final step: Trip Completed
    $completedLog = $findTripStatusLog(TripStatusEnum::COMPLETED);
    if ($completedLog) {
        $timelineSteps[] = [
            'title' => 'Trip Completed',
            'icon' => '🏁',
            'timestamp' => $completedLog->created_at,
            'status' => 'completed',
        ];
    } elseif ($tripStatus === TripStatusEnum::COMPLETED) {
        $timelineSteps[] = [
            'title' => 'Trip Completed',
            'icon' => '🏁',
            'timestamp' => null,
            'status' => 'current',
            'isCurrent' => true,
        ];
    } else {
        $timelineSteps[] = [
            'title' => 'Trip Completed',
            'icon' => '🏁',
            'timestamp' => null,
            'status' => 'pending',
        ];
    }

    // Check for cancellation
    if (in_array($tripStatus, [TripStatusEnum::CANCELED_BY_CUSTOMER, TripStatusEnum::CANCELLED_BY_RIDER], true)) {
        $cancelLog = $findTripStatusLog($tripStatus);
        $timelineSteps = array_filter($timelineSteps, fn($step) => $step['status'] !== 'pending');
        $timelineSteps[] = [
            'title' => 'Trip Cancelled',
            'icon' => '❌',
            'timestamp' => $cancelLog?->created_at,
            'status' => 'cancelled',
        ];
    }
@endphp

<div class="space-y-6">
    {{-- Demand Trip Notice --}}
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
                           class="font-semibold underline hover:no-underline"
                           target="_blank">
                            #{{ $trip->demand_trip_id }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Progressive Timeline --}}
    <div class="space-y-6">
        <div class="space-y-3">
            @foreach ($timelineSteps as $index => $step)
                @php
                    $isLast = $loop->last;
                    $isCurrent = $step['isCurrent'] ?? false;
                    $isCompleted = $step['status'] === 'completed';
                    $isPending = $step['status'] === 'pending';
                    $isCancelled = $step['status'] === 'cancelled';
                    $isWait = $step['isWait'] ?? false;
                @endphp

                <div class="relative flex items-center gap-4 rounded-xl border p-4 transition-all
                    {{ $isCurrent ? 'border-blue-400 bg-blue-50/50 shadow-md dark:border-blue-600 dark:bg-blue-900/20' :
                       ($isCompleted ? 'border-green-200 bg-white dark:border-green-800 dark:bg-gray-800/50' :
                       ($isCancelled ? 'border-red-200 bg-red-50/30 dark:border-red-800 dark:bg-red-900/10' :
                       'border-gray-200 bg-gray-50/30 dark:border-gray-700 dark:bg-gray-800/30')) }}">

                    {{-- Connecting Line (positioned absolutely to connect to next item) --}}
                    @if (!$isLast)
                        <div class="absolute left-[2.125rem] top-[4rem] h-[calc(100%+0.75rem)] w-0.5
                            {{ $isCompleted ? 'bg-green-400 dark:bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                        </div>
                    @endif

                    {{-- Timeline Icon --}}
                    <div class="relative z-10 flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full shadow-sm
                            {{ $isCompleted ? 'bg-green-500 dark:bg-green-600' :
                               ($isCurrent ? 'bg-blue-500 shadow-lg ring-4 ring-blue-200 dark:bg-blue-600 dark:ring-blue-800' :
                               ($isCancelled ? 'bg-red-500 dark:bg-red-600' :
                               'bg-gray-300 dark:bg-gray-600')) }}">
                            <span class="text-2xl">{{ $step['icon'] }}</span>
                        </div>
                    </div>

                    {{-- Step Content - All in one horizontal line --}}
                    <div class="flex flex-1 items-center justify-between gap-4">
                        {{-- Left: Title, Badges, and Location (all inline) --}}
                        <div class="flex items-center gap-2 flex-wrap flex-1">
                            <h4 class="text-base font-bold leading-tight whitespace-nowrap
                                {{ $isCurrent ? 'text-blue-900 dark:text-blue-100' :
                                   ($isCompleted ? 'text-gray-900 dark:text-gray-100' :
                                   ($isCancelled ? 'text-red-900 dark:text-red-100' :
                                   'text-gray-600 dark:text-gray-400')) }}">
                                {{ $step['title'] }}
                            </h4>

                            @if ($isCurrent)
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-500 px-2.5 py-0.5 text-xs font-bold text-white shadow-sm animate-pulse">
                                    <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                    {{ trans('trips.admin.fields.now') }}
                                </span>
                            @endif

                            @if ($isWait)
                                <span class="inline-flex items-center gap-1 rounded-full bg-orange-500 px-2.5 py-0.5 text-xs font-bold text-white">
                                    ⏱️ {{ trans('trips.admin.fields.waiting') }}
                                </span>
                            @endif

                            @if (isset($step['location']) && $step['location']->location_sub_title)
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    📍 {{ $step['location']->location_sub_title }}
                                </span>
                            @endif
                        </div>

                        {{-- Right: Timestamp (without background box) --}}
                        <div class="flex-shrink-0 text-right">
                            @if ($step['timestamp'])
                                <div class="text-sm font-bold
                                    {{ $isCurrent ? 'text-blue-900 dark:text-blue-100' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ $step['timestamp']->diffForHumans() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $step['timestamp']->format('M d, Y H:i') }}
                                </div>
                            @elseif ($isPending)
                                <span class="text-sm font-semibold italic text-gray-500 dark:text-gray-400">
                                    {{ trans('trips.admin.fields.pending') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
