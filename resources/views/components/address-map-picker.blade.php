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
    @include('partials.google-maps-loader')
    <div
        wire:ignore
        x-data="{
            map: null,
            marker: null,
            geocoder: null,
            inited: false,
            lastLat: {{ $lat }},
            lastLng: {{ $lng }},
            wireLat: @js($wireLat),
            wireLng: @js($wireLng),
            syncFromWire() {
                if (!this.inited || typeof $wire === 'undefined') return;
                const lat = $wire.get(this.wireLat);
                const lng = $wire.get(this.wireLng);
                if (lat == null || lng == null || typeof lat !== 'number' || typeof lng !== 'number') return;
                const pos = { lat: Number(lat), lng: Number(lng) };
                this.map.setCenter(pos);
                this.marker.setPosition(pos);
                this.lastLat = pos.lat;
                this.lastLng = pos.lng;
                if (typeof window !== 'undefined') window.__structureMapPosition = { lat: pos.lat, lng: pos.lng };
            },
            async initMap() {
                if (this.inited) return;
                const el = this.$refs.map;
                if (!el) return;
                await new Promise(r => setTimeout(r, 150));
                if (!el.offsetParent || el.offsetWidth === 0) return;
                this.inited = true;
                try {
                    await Promise.all([
                        google.maps.importLibrary('maps'),
                        google.maps.importLibrary('geocoding'),
                    ]);
                    this.geocoder = new google.maps.Geocoder();
                    let center = { lat: {{ $lat }}, lng: {{ $lng }} };
                    if (typeof $wire !== 'undefined') {
                        const wLat = $wire.get(this.wireLat);
                        const wLng = $wire.get(this.wireLng);
                        if (wLat != null && wLng != null && typeof wLat === 'number' && typeof wLng === 'number') {
                            center = { lat: Number(wLat), lng: Number(wLng) };
                        }
                    }
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
                    this.lastLat = center.lat;
                    this.lastLng = center.lng;
                    if (typeof window !== 'undefined') window.__structureMapPosition = { lat: center.lat, lng: center.lng };
                } catch (e) {
                    console.error('Google Map init failed:', e);
                    this.inited = false;
                }
            },
            onMarkerDragEnd() {
                const pos = this.marker.getPosition();
                const lat = pos.lat();
                const lng = pos.lng();
                this.lastLat = lat;
                this.lastLng = lng;
                if (typeof window !== 'undefined') window.__structureMapPosition = { lat, lng };
                const wireAddr = @js($wireAddr);
                const wireLat = @js($wireLat);
                const wireLng = @js($wireLng);
                if (typeof $wire !== 'undefined') {
                    $wire.set(wireLat, lat);
                    $wire.set(wireLng, lng);
                }
                this.geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                    if (status === 'OK' && results[0] && typeof $wire !== 'undefined') {
                        $wire.set(wireAddr, results[0].formatted_address);
                    }
                });
            },
            getCurrentPosition() {
                if (this.marker) {
                    const pos = this.marker.getPosition();
                    return { lat: pos.lat(), lng: pos.lng() };
                }
                return this.lastLat != null && this.lastLng != null
                    ? { lat: this.lastLat, lng: this.lastLng }
                    : null;
            }
        }"
        x-init="$nextTick(() => { if (typeof $wire !== 'undefined') { $wire.watch(wireLat, () => syncFromWire()); $wire.watch(wireLng, () => syncFromWire()); } })"
        x-intersect="initMap(); setTimeout(() => syncFromWire(), 200)"
        class="rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-600"
    >
        <div x-ref="map" style="height: {{ $height }}; width: 100%; min-height: 150px;"></div>
        <div class="px-2 py-1 text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800">
            {{ __('Drag the pin to adjust the exact location') }}
        </div>
    </div>
@endif
