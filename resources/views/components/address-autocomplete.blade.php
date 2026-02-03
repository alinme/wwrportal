@props([
    'label' => __('Address'),
    'placeholder' => __('Start typing address...'),
    'fillCity' => null,
    'fillState' => null,
    'fillCountry' => null,
    'fillLatitude' => null,
    'fillLongitude' => null,
])

@php
    $apiKey = config('services.google.places_api_key', '');
    $fillProps = array_filter([
        'city' => $fillCity,
        'state' => $fillState,
        'country' => $fillCountry,
        'latitude' => $fillLatitude,
        'longitude' => $fillLongitude,
    ]);
@endphp

@if($apiKey)
    <div
        x-data="{
            init() {
                const loadPlaces = () => {
                    if (typeof google === 'undefined') {
                        setTimeout(loadPlaces, 100);
                        return;
                    }
                    const input = this.$refs.input;
                    if (!input) return;
                    const autocomplete = new google.maps.places.Autocomplete(input, {
                        types: ['address'],
                        componentRestrictions: { country: ['ro'] },
                        fields: ['formatted_address', 'address_components', 'geometry']
                    });
                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        const address = place.formatted_address || input.value;
                        input.value = address;
                        input.dispatchEvent(new Event('input', { bubbles: true }));

                        const fill = @js($fillProps);
                        if (Object.keys(fill).length && $wire) {
                            const components = (place.address_components || []).reduce((acc, c) => {
                                if (c.types.includes('locality')) acc.city = c.long_name;
                                if (c.types.includes('administrative_area_level_1')) acc.state = c.long_name;
                                if (c.types.includes('country')) acc.country = c.long_name;
                                return acc;
                            }, {});
                            if (place.geometry?.location) {
                                components.latitude = place.geometry.location.lat();
                                components.longitude = place.geometry.location.lng();
                            }
                            Object.entries(fill).forEach(([key, prop]) => {
                                const val = components[key];
                                if (prop && val != null) $wire.set(prop, key === 'latitude' || key === 'longitude' ? Number(val) : String(val));
                            });
                        }
                    });
                };
                $nextTick(() => loadPlaces());
            }
        }"
        x-init="init()"
    >
        <flux:input
            {{ $attributes->merge(['label' => $label, 'placeholder' => $placeholder]) }}
            x-ref="input"
        />
    </div>
@else
    <flux:input
        {{ $attributes->merge(['label' => $label, 'placeholder' => $placeholder]) }}
    />
@endif

@if($apiKey)
    @once
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=places&callback=Function.prototype" async defer></script>
    @endonce
@endif
