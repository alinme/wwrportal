@if($apiKey)
    @include('partials.google-maps-loader')
    <div
        x-data="{
            async init() {
                const container = this.$refs.autocompleteContainer;
                if (!container) return;
                try {
                    await google.maps.importLibrary('places');
                    const romaniaBounds = { north: 48.3, south: 43.6, west: 20.2, east: 29.7 };
                    const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement({
                        locationRestriction: romaniaBounds,
                    });
                    placeAutocomplete.id = 'place-autocomplete-' + Math.random().toString(36).slice(2);
                    container.appendChild(placeAutocomplete);
                    placeAutocomplete.addEventListener('gmp-placeselect', async (ev) => {
                        const placePrediction = ev.placePrediction || ev.place;
                        const p = placePrediction && placePrediction.toPlace ? placePrediction.toPlace() : placePrediction;
                        if (!p) return;
                        await p.fetchFields({ fields: ['formattedAddress'] });
                        const address = p.formattedAddress || '';
                        if (typeof $wire !== 'undefined') $wire.set('address', address);
                    });
                } catch (e) {
                    console.error('Google Places init failed:', e);
                }
            }
        }"
        x-init="$nextTick(() => init())"
    >
        <label class="flux-label mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label ?: __('Address') }}</label>
        <div x-ref="autocompleteContainer" class="[&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-zinc-300 [&_input]:dark:border-zinc-600 [&_input]:px-3 [&_input]:py-2 [&_input]:text-sm [&_input]:bg-white [&_input]:dark:bg-zinc-800"></div>
    </div>
@else
    <flux:input
        wire:model.live="address"
        :label="$label ?: __('Address')"
        :placeholder="$placeholder ?: __('Start typing address...')"
        {{ $attributes->except('label', 'placeholder') }}
    />
@endif
