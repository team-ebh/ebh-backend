@php
    use App\Enums\Trip\TripStatusEnum;
    use App\Enums\Trip\TripLocationStatusEnum;

    $trip = $getState()['trip'] ?? null;
    if (!$trip) {
        return;
    }

    $trip->load([
        'locations:id,trip_id,latitude,longitude,type,location_title,location_sub_title,sequence,status',
        'rider:id,full_name,phone_number,email,latitude,longitude,last_location_update'
    ]);

    if ($trip->locations->isEmpty()) {
        echo '<div class="p-6 text-center text-gray-500">No locations available</div>';
        return;
    }

    $locations = $trip->locations->map(function ($location) {
        return [
            'lat' => (float) $location->latitude,
            'lng' => (float) $location->longitude,
            'type' => $location->type->value,
            'label' => $location->type->getLabel(),
            'title' => $location->location_title,
            'sequence' => $location->sequence,
            'isFinished' => $location->isFinished(),
        ];
    })->toArray();

    // Only show rider location for active trips (not completed/canceled)
    $riderLocation = null;
    $isFinishedTrip = in_array($trip->status, [
        TripStatusEnum::COMPLETED,
        TripStatusEnum::CANCELED_BY_CUSTOMER,
        TripStatusEnum::CANCELLED_BY_RIDER,
    ], true);

    if (!$isFinishedTrip && $trip->rider && $trip->rider->latitude && $trip->rider->longitude) {
        $riderLocation = [
            'lat' => (float) $trip->rider->latitude,
            'lng' => (float) $trip->rider->longitude,
            'name' => $trip->rider->full_name,
        ];
    }

    $firstLocation = $trip->locations->first();
    $lat = (float) $firstLocation->latitude;
    $lng = (float) $firstLocation->longitude;
    $mapId = 'map-' . uniqid();
@endphp

<div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <div id="{{ $mapId }}" style="width: 100%; height: 600px; background: #f0f0f0;"></div>

    {{-- Locations List --}}
    <div class="mt-6 overflow-x-auto rounded-lg bg-white p-8 dark:bg-gray-800" style="line-height: 1.8;">
        <h3 class="mb-6 text-base font-semibold text-gray-900 dark:text-white">
            {{ trans('trips.admin.sections.locations') }}
        </h3>
        <div class="space-y-3">
            @foreach($trip->locations->sortBy('sequence') as $location)
                <div class="text-sm text-gray-900 dark:text-white">
                    <span class="text-base">{{ $location->type->value === 'origin' ? '🟢' : '🔵' }}</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $location->type->getLabel() }}</span>
                    <span class="mx-2">•</span>
                    <span class="text-base">📍</span>
                    <span class="font-semibold">{{ $location->location_title }}</span>
                    @if($location->location_sub_title)
                        <span class="mx-2">•</span>
                        <span class="text-base">📝</span>
                        <span class="text-gray-600 dark:text-gray-400">{{ $location->location_sub_title }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            setTimeout(function () {
                var container = document.getElementById('{{ $mapId }}');
                if (!container || typeof L === 'undefined') {
                    console.error('Map container or Leaflet not found');
                    return;
                }

                var map = L.map('{{ $mapId }}').setView([{{ $lat }}, {{ $lng }}], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                var locations = {!! json_encode($locations) !!};
                var riderLocation = {!! json_encode($riderLocation) !!};

                console.log('Map: ' + locations.length + ' locations' + (riderLocation ? ' + rider' : ''));

                // Add location markers
                locations.forEach(function (location) {
                    var marker = L.marker([location.lat, location.lng]).addTo(map);
                    marker.bindPopup('<b>' + location.label + '</b><br>' + location.title);
                });

                // Add rider marker if available
                if (riderLocation) {
                    var riderMarker = L.circleMarker([riderLocation.lat, riderLocation.lng], {
                        radius: 10,
                        fillColor: '#EF4444',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(map);

                    riderMarker.bindPopup('<b>Rider: ' + riderLocation.name + '</b><br>Current Location');
                    console.log('✓ Rider marker added');
                }

                // Draw route using OSRM routing service
                var routePoints = [];

                // Sort locations by sequence
                var sortedLocations = locations.sort(function (a, b) {
                    return a.sequence - b.sequence;
                });

                // Build route coordinates
                if (riderLocation) {
                    // Active trip: Start from rider location
                    routePoints.push(riderLocation.lng + ',' + riderLocation.lat);

                    // Find next incomplete location (not finished)
                    var incompleteLocations = sortedLocations.filter(function(loc) {
                        return !loc.isFinished;
                    });

                    // Add only incomplete locations to route
                    incompleteLocations.forEach(function (loc) {
                        routePoints.push(loc.lng + ',' + loc.lat);
                    });
                } else {
                    // Finished trip: Show route through all locations
                    sortedLocations.forEach(function (loc) {
                        routePoints.push(loc.lng + ',' + loc.lat);
                    });
                }

                if (routePoints.length >= 2) {
                    var coords = routePoints.join(';');
                    var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/' + coords + '?overview=full&geometries=geojson';

                    fetch(osrmUrl)
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.code === 'Ok' && data.routes && data.routes[0]) {
                                var route = data.routes[0].geometry.coordinates;
                                var routeLatLngs = route.map(function (coord) {
                                    return [coord[1], coord[0]];
                                });

                                L.polyline(routeLatLngs, {
                                    color: '#3B82F6',
                                    weight: 4,
                                    opacity: 0.7
                                }).addTo(map);

                                console.log('Route added: ' + (riderLocation ? 'rider → incomplete locations' : 'all locations'));
                            } else {
                                drawStraightLine();
                            }
                        })
                        .catch(function (error) {
                            console.error('Route error:', error);
                            drawStraightLine();
                        });
                }

                function drawStraightLine() {
                    var linePoints = [];

                    if (riderLocation) {
                        linePoints.push([riderLocation.lat, riderLocation.lng]);

                        // Add only incomplete locations (not finished)
                        var incompleteLocations = sortedLocations.filter(function(loc) {
                            return !loc.isFinished;
                        });
                        incompleteLocations.forEach(function (loc) {
                            linePoints.push([loc.lat, loc.lng]);
                        });
                    } else {
                        // Finished trip: Show all locations
                        sortedLocations.forEach(function (loc) {
                            linePoints.push([loc.lat, loc.lng]);
                        });
                    }

                    L.polyline(linePoints, {
                        color: '#3B82F6',
                        weight: 4,
                        opacity: 0.7,
                        dashArray: '10, 5'
                    }).addTo(map);
                }

                // Fit bounds to show all markers including rider
                var allPoints = locations.map(function (loc) {
                    return [loc.lat, loc.lng];
                });

                if (riderLocation) {
                    allPoints.push([riderLocation.lat, riderLocation.lng]);
                }

                if (allPoints.length > 1) {
                    var bounds = L.latLngBounds(allPoints);
                    map.fitBounds(bounds, {padding: [50, 50]});
                }
            }, 1000);
        })();
    </script>
</div>
