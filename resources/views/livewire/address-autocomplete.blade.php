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
                        fields: ['formatted_address', 'address_components']
                    });
                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        const address = place.formatted_address || input.value;
                        $wire.set('address', address);
                    });
                };
                $nextTick(() => loadPlaces());
            }
        }"
        x-init="init()"
    >
        <flux:input
            wire:model.live="address"
            :label="$label ?: __('Address')"
            :placeholder="$placeholder ?: __('Start typing address...')"
            x-ref="input"
            {{ $attributes->except('label', 'placeholder') }}
        />
    </div>
@else
    <flux:input
        wire:model.live="address"
        :label="$label ?: __('Address')"
        :placeholder="$placeholder ?: __('Start typing address...')"
        {{ $attributes->except('label', 'placeholder') }}
    />
@endif

@if($apiKey)
    @once
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=places&callback=Function.prototype" async defer></script>
    @endonce
@endif
