@props([
    'latitude' => null,
    'longitude' => null,
    'height' => '200px',
    'wireAddress' => 'address',
    'wireLatitude' => 'latitude',
    'wireLongitude' => 'longitude',
])

@php
    $apiKey = config('services.google.places_api_key', '');
    $lat = $latitude ? (float) $latitude : 45.9432;
    $lng = $longitude ? (float) $longitude : 24.9668;
    $wireAddr = $wireAddress ?? 'address';
    $wireLat = $wireLatitude ?? 'latitude';
    $wireLng = $wireLongitude ?? 'longitude';
@endphp

@if($apiKey)
    <div
        x-data="{
            map: null,
            marker: null,
            geocoder: null,
            init() {
                const loadMap = () => {
                    if (typeof google === 'undefined') {
                        setTimeout(loadMap, 100);
                        return;
                    }
                    const el = this.$refs.map;
                    if (!el) return;
                    this.geocoder = new google.maps.Geocoder();
                    const center = { lat: {{ $lat }}, lng: {{ $lng }} };
                    this.map = new google.maps.Map(el, {
                        center,
                        zoom: 16,
                        mapTypeControl: true,
                        streetViewControl: false,
                        fullscreenControl: true,
                        zoomControl: true,
                    });
                    this.marker = new google.maps.Marker({
                        map: this.map,
                        position: center,
                        draggable: true,
                        title: '{{ __("Drag to adjust location") }}',
                    });
                    this.marker.addListener('dragend', () => this.onMarkerDragEnd());
                };
                $nextTick(() => loadMap());
            },
            onMarkerDragEnd() {
                const pos = this.marker.getPosition();
                const lat = pos.lat();
                const lng = pos.lng();
                const wireAddr = @js($wireAddr);
                const wireLat = @js($wireLat);
                const wireLng = @js($wireLng);
                if ($wire) {
                    $wire.set(wireLat, lat);
                    $wire.set(wireLng, lng);
                }
                this.geocoder.geocode({ location: pos }, (results, status) => {
                    if (status === 'OK' && results[0] && $wire) {
                        $wire.set(wireAddr, results[0].formatted_address);
                    }
                });
            }
        }"
        x-init="init()"
        class="rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-600"
    >
        <div x-ref="map" style="height: {{ $height }}; width: 100%;"></div>
        <div class="px-2 py-1 text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800">
            {{ __('Drag the pin to adjust the exact location') }}
        </div>
    </div>
@endif
