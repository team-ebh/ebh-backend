@php
    use App\Enums\Trip\TripLocationTypeEnum;
    use App\Enums\Trip\TripStatusEnum;

    $trip = $getState()['trip'] ?? null;
    $hasData = false;
    $locations = [];
    $riderLocation = null;
    $firstLocation = null;
    $mapId = 'trip-map-0';

    if ($trip) {
        $trip->load([
            'locations:id,trip_id,latitude,longitude,type,location_title,location_sub_title,sequence,status',
            'rider:id,full_name,phone_number,email,latitude,longitude,last_location_update'
        ]);

        if ($trip->locations->isNotEmpty()) {
            $hasData = true;

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
            $mapId = 'trip-map-' . $trip->id;
        }
    }
@endphp

<div wire:ignore>
    @if(!$hasData)
        <div class="p-6 text-center text-gray-500">No locations available</div>
    @else
        <div class="mb-6 overflow-x-auto rounded-lg bg-white p-8 dark:bg-gray-800" style="line-height: 1.8;">
            <h3 class="mb-6 text-base font-semibold text-gray-900 dark:text-white">{{ trans('trips.admin.sections.locations') }}</h3>
            <div class="space-y-3">
                @foreach($trip->locations->sortBy('sequence') as $location)
                    <div class="text-sm text-gray-900 dark:text-white">
                        <span class="text-base">{{ $location->type->value === TripLocationTypeEnum::ORIGIN->value ? '🟢' : '🔵' }}</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $location->type->getLabel() }}</span>
                        <span class="mx-2">•</span>
                        <span class="text-base">📍</span>
                        <span class="font-semibold">{{ $location->location_title }}</span>
                        <span class="text-gray-400 text-xs ml-2">({{ $location->latitude }}, {{ $location->longitude }})</span>
                        @if($location->location_sub_title)
                            <span class="mx-2">•</span>
                            <span class="text-base">📝</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $location->location_sub_title }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
        <style>.leaflet-pane svg{z-index:auto!important}.leaflet-overlay-pane{z-index:400!important}</style>
        <div id="{{ $mapId }}" style="width:100%;height:500px;background:#e5e7eb;border-radius:8px;position:relative;z-index:0;"></div>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
        (function(){
            const C = {
                id: '{{ $mapId }}',
                loc: @json($locations),
                rider: @json($riderLocation),
                oType: {{ TripLocationTypeEnum::ORIGIN->value }},
                cLat: {{ $firstLocation ? (float)$firstLocation->latitude : 0 }},
                cLng: {{ $firstLocation ? (float)$firstLocation->longitude : 0 }}
            };

            console.log('[TripMap] Config:', C);
            console.log('[TripMap] Locations count:', C.loc ? C.loc.length : 0);
            console.log('[TripMap] Locations:', C.loc);
            console.log('[TripMap] Rider:', C.rider);

            if(window['_m_'+C.id]) {
                console.log('[TripMap] Already initialized, skipping');
                return;
            }
            window['_m_'+C.id] = 1;

            function go(){
                const el = document.getElementById(C.id);
                if(!el || typeof L === 'undefined'){
                    console.log('[TripMap] Waiting for element or Leaflet...');
                    setTimeout(go, 100);
                    return;
                }
                if(el._leaflet_id) {
                    console.log('[TripMap] Map already exists on element');
                    return;
                }

                console.log('[TripMap] Creating map...');
                const m = L.map(C.id).setView([C.cLat, C.cLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);

                // Sort locations by sequence
                const sorted = C.loc.slice().sort((a, b) => a.sequence - b.sequence);
                console.log('[TripMap] Sorted locations:', sorted);

                // Add markers for all locations
                const bounds = [];
                sorted.forEach(function(l, index){
                    console.log('[TripMap] Adding marker ' + (index+1) + '/' + sorted.length + ':', l);
                    const color = l.type === C.oType ? '#10B981' : '#3B82F6';
                    L.circleMarker([l.lat, l.lng], {
                        radius: 14,
                        fillColor: color,
                        color: '#fff',
                        weight: 3,
                        fillOpacity: 0.9
                    }).addTo(m).bindPopup('<b>' + l.label + '</b><br>' + l.title);
                    bounds.push([l.lat, l.lng]);
                });
                console.log('[TripMap] Total markers added:', bounds.length);

                // Add rider marker if exists
                if(C.rider){
                    console.log('[TripMap] Adding rider marker:', C.rider);
                    L.circleMarker([C.rider.lat, C.rider.lng], {
                        radius: 16,
                        fillColor: '#EF4444',
                        color: '#fff',
                        weight: 3,
                        fillOpacity: 0.9
                    }).addTo(m).bindPopup('<b>Rider:</b> ' + C.rider.name);
                    bounds.push([C.rider.lat, C.rider.lng]);
                }

                // Fit bounds
                if(bounds.length > 1) {
                    console.log('[TripMap] Fitting bounds for', bounds.length, 'points');
                    m.fitBounds(bounds, {padding: [40, 40]});
                }

                // Draw route line
                drawRoute(m, sorted);
            }

            function drawRoute(m, sorted){
                console.log('[TripMap] Drawing route...');

                const routePoints = [];

                // Get incomplete locations
                const incomplete = sorted.filter(l => !l.isFinished);
                console.log('[TripMap] Incomplete locations:', incomplete.length);

                if(C.rider && incomplete.length > 0){
                    // Active trip: rider → incomplete locations
                    routePoints.push([C.rider.lat, C.rider.lng]);
                    incomplete.forEach(l => routePoints.push([l.lat, l.lng]));
                    console.log('[TripMap] Route: rider → incomplete locations');
                } else {
                    // Completed trip or no rider: show full route through all locations
                    sorted.forEach(l => routePoints.push([l.lat, l.lng]));
                    console.log('[TripMap] Route: all locations (completed/no rider)');
                }

                console.log('[TripMap] Route points:', routePoints);

                // Get real road route from OSRM
                if(routePoints.length >= 2){
                    const osrmPoints = routePoints.map(p => p[1] + ',' + p[0]).join(';');
                    console.log('[TripMap] Fetching OSRM route...');
                    fetch('https://router.project-osrm.org/route/v1/driving/' + osrmPoints + '?overview=full&geometries=geojson')
                        .then(r => r.json())
                        .then(data => {
                            console.log('[TripMap] OSRM response:', data);
                            if(data.code === 'Ok' && data.routes && data.routes[0]){
                                const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                                L.polyline(coords, {
                                    color: '#2563EB',
                                    weight: 5,
                                    opacity: 0.8
                                }).addTo(m);
                                console.log('[TripMap] OSRM route added');
                            }
                        })
                        .catch(err => {
                            console.log('[TripMap] OSRM error:', err);
                        });
                }
            }

            setTimeout(go, 300);
        })();
        </script>
    @endif
</div>
