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
                loc:@json($locations),
                rider:@json($riderLocation),
                oType: {{ TripLocationTypeEnum::ORIGIN->value }},
                cLat: {{ $firstLocation?(float)$firstLocation->latitude:0 }},
                cLng: {{ $firstLocation?(float)$firstLocation->longitude:0 }}
            };
            if(window['_m_'+C.id])return;window['_m_'+C.id]=1;
            function go(){
                const el = document.getElementById(C.id);
                if(!el||typeof L==='undefined'){setTimeout(go,100);return;}
                if(el._leaflet_id)return;
                const m = L.map(C.id).setView([C.cLat, C.cLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);
                const b = [];
                C.loc.forEach(function(l){
                    var c=l.type===C.oType?'#10B981':'#3B82F6';
                    L.circleMarker([l.lat,l.lng],{radius:14,fillColor:c,color:'#fff',weight:3,fillOpacity:0.9}).addTo(m).bindPopup('<b>'+l.label+'</b><br>'+l.title);
                    b.push([l.lat,l.lng]);
                });
                if(C.rider){
                    L.circleMarker([C.rider.lat,C.rider.lng],{radius:16,fillColor:'#EF4444',color:'#fff',weight:3,fillOpacity:0.9}).addTo(m).bindPopup('<b>Rider:</b> '+C.rider.name);
                    b.push([C.rider.lat,C.rider.lng]);
                }
                if(b.length>1)m.fitBounds(b,{padding:[40,40]});

                // Draw route
                const sorted = C.loc.slice().sort(function (a, b) {
                    return a.sequence - b.sequence;
                });
                const rp = [];
                if(C.rider){
                    // Active trip: rider -> incomplete locations
                    rp.push(C.rider.lng+','+C.rider.lat);
                    sorted.filter(function(l){return !l.isFinished;}).forEach(function(l){
                        rp.push(l.lng+','+l.lat);
                    });
                }else{
                    // Finished trip: all locations
                    sorted.forEach(function(l){rp.push(l.lng+','+l.lat);});
                }
                if(rp.length>=2){
                    fetch('https://router.project-osrm.org/route/v1/driving/'+rp.join(';')+'?overview=full&geometries=geojson')
                        .then(function(r){return r.json();})
                        .then(function(d){
                            if(d.code==='Ok'&&d.routes&&d.routes[0]){
                                var coords=d.routes[0].geometry.coordinates.map(function(c){return[c[1],c[0]];});
                                L.polyline(coords,{color:'#3B82F6',weight:5,opacity:0.7}).addTo(m);
                            }else{
                                drawFallback();
                            }
                        }).catch(function(){drawFallback();});
                }
                function drawFallback(){
                    const pts = [];
                    if(C.rider){
                        pts.push([C.rider.lat,C.rider.lng]);
                        sorted.filter(function(l){return !l.isFinished;}).forEach(function(l){pts.push([l.lat,l.lng]);});
                    }else{
                        sorted.forEach(function(l){pts.push([l.lat,l.lng]);});
                    }
                    if(pts.length>=2)L.polyline(pts,{color:'#3B82F6',weight:4,opacity:0.6,dashArray:'8,8'}).addTo(m);
                }
            }
            setTimeout(go,300);
        })();
        </script>
    @endif
</div>
