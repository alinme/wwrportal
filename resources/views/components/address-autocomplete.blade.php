@props([
    'label' => __('Address'),
    'placeholder' => __('Start typing address...'),
    'model' => 'address',
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
    @include('partials.google-maps-loader')
    <div
        x-data="{
            suggestions: [],
            open: false,
            loading: false,
            sessionToken: null,
            requestId: 0,
            fill: @js($fillProps),
            hasFill: @js(count($fillProps) > 0),
            model: @js($model),
            romaniaBounds: { north: 48.3, south: 43.6, west: 20.2, east: 29.7 },
            async ensurePlaces() {
                if (window.__placesLib) return window.__placesLib;
                window.__placesLib = await Promise.all([
                    google.maps.importLibrary('places'),
                    google.maps.importLibrary('geocoding'),
                ]);
                return window.__placesLib;
            },
            async fetchSuggestions() {
                const input = this.$refs.input;
                const q = (input && input.value) ? input.value.trim() : '';
                if (q.length < 2) { this.suggestions = []; this.open = false; return; }
                this.loading = true;
                const reqId = ++this.requestId;
                try {
                    const [placesLib] = await this.ensurePlaces();
                    const { AutocompleteSessionToken, AutocompleteSuggestion } = placesLib;
                    if (!this.sessionToken) this.sessionToken = new AutocompleteSessionToken();
                    const request = {
                        input: q,
                        sessionToken: this.sessionToken,
                        locationRestriction: this.romaniaBounds,
                    };
                    const { suggestions } = await AutocompleteSuggestion.fetchAutocompleteSuggestions(request);
                    if (reqId !== this.requestId) return;
                    this.suggestions = (suggestions || []).map(s => ({ raw: s, label: this.getSuggestionLabel(s) }));
                    this.open = this.suggestions.length > 0;
                } catch (e) {
                    if (reqId === this.requestId) { this.suggestions = []; this.open = false; }
                    console.error('Places suggestions failed:', e);
                }
                this.loading = false;
            },
            async selectSuggestion(suggestionOrWrapper) {
                console.log('[address-autocomplete] selectSuggestion called', { suggestionOrWrapper, type: typeof suggestionOrWrapper, isArray: Array.isArray(suggestionOrWrapper), keys: suggestionOrWrapper && typeof suggestionOrWrapper === 'object' ? Object.keys(suggestionOrWrapper) : null });
                this.open = false;
                this.suggestions = [];
                this.sessionToken = null;
                const input = this.$refs.input;
                let address = '';
                const suggestion = suggestionOrWrapper && typeof suggestionOrWrapper === 'object' && suggestionOrWrapper.label != null ? suggestionOrWrapper.raw : suggestionOrWrapper;
                if (suggestionOrWrapper && typeof suggestionOrWrapper === 'object' && typeof suggestionOrWrapper.label === 'string' && suggestionOrWrapper.label) {
                    address = suggestionOrWrapper.label;
                    console.log('[address-autocomplete] using wrapper.label', address);
                }
                if (!address && Array.isArray(suggestion) && suggestion[2] && suggestion[2][0]) {
                    address = suggestion[2][0];
                    console.log('[address-autocomplete] using array[2][0]', address);
                }
                if (!address && suggestion && typeof this.getSuggestionLabel === 'function') {
                    address = this.getSuggestionLabel(suggestion);
                    console.log('[address-autocomplete] using getSuggestionLabel', address);
                }
                console.log('[address-autocomplete] resolved address', address, 'input', !!input, '$wire', typeof $wire !== 'undefined');
                if (address) {
                    if (input) { input.value = address; console.log('[address-autocomplete] set input.value'); }
                    if (typeof $wire !== 'undefined') { $wire.set(this.model, address); console.log('[address-autocomplete] $wire.set model', this.model); }
                    if (this.hasFill && typeof google !== 'undefined') {
                        try {
                            const geocoder = new google.maps.Geocoder();
                            const res = await new Promise((resolve) => {
                                geocoder.geocode({ address }, (results, status) => {
                                    resolve(status === 'OK' && results && results[0] ? results[0] : null);
                                });
                            });
                            if (res) {
                                console.log('[address-autocomplete] geocode result', res);
                                const loc = res.geometry?.location;
                                const lat = loc && (typeof loc.lat === 'function' ? loc.lat() : loc.lat);
                                const lng = loc && (typeof loc.lng === 'function' ? loc.lng() : loc.lng);
                                if (lat != null && lng != null) {
                                    if (this.fill.latitude) $wire.set(this.fill.latitude, lat);
                                    if (this.fill.longitude) $wire.set(this.fill.longitude, lng);
                                    console.log('[address-autocomplete] set lat/lng', lat, lng);
                                }
                                const acc = {};
                                (res.address_components || []).forEach(c => {
                                    if (c.types.includes('locality')) acc.city = c.long_name;
                                    if (c.types.includes('administrative_area_level_1')) acc.state = c.long_name;
                                    if (c.types.includes('country')) acc.country = c.long_name;
                                });
                                Object.entries(this.fill).forEach(([key, prop]) => {
                                    const val = acc[key];
                                    if (prop != null && val != null && val !== '' && key !== 'latitude' && key !== 'longitude') $wire.set(prop, String(val));
                                });
                            }
                        } catch (e) { console.error('Geocode failed:', e); }
                    }
                    return;
                }
                const placePrediction = suggestion && suggestion.placePrediction;
                if (!placePrediction || typeof placePrediction.toPlace !== 'function') return;
                try {
                    const place = placePrediction.toPlace();
                    await place.fetchFields({
                        fields: ['formattedAddress', 'location', 'addressComponents']
                    });
                    const addr = place.formattedAddress || '';
                    if (input) input.value = addr;
                    if (typeof $wire !== 'undefined') {
                        $wire.set(this.model, addr);
                        if (this.hasFill) {
                            const acc = {};
                            if (place.location) {
                                acc.latitude = typeof place.location.lat === 'function' ? place.location.lat() : place.location.lat;
                                acc.longitude = typeof place.location.lng === 'function' ? place.location.lng() : place.location.lng;
                            }
                            if (place.addressComponents && Array.isArray(place.addressComponents)) {
                                place.addressComponents.forEach(c => {
                                    const types = c.types || [];
                                    const longText = (c.longText !== undefined) ? c.longText : (c.long_name !== undefined ? c.long_name : null);
                                    if (longText) {
                                        if (types.includes('locality')) acc.city = longText;
                                        if (types.includes('administrative_area_level_1')) acc.state = longText;
                                        if (types.includes('country')) acc.country = longText;
                                    }
                                });
                            }
                            if ((!acc.city || !acc.state || !acc.country) && acc.latitude != null && acc.longitude != null) {
                                const geocoder = new google.maps.Geocoder();
                                const res = await new Promise((resolve) => {
                                    geocoder.geocode({ location: { lat: acc.latitude, lng: acc.longitude } }, (results, status) => {
                                        resolve(status === 'OK' && results && results[0] ? results[0] : null);
                                    });
                                });
                                if (res && res.address_components) {
                                    res.address_components.forEach(c => {
                                        if (c.types.includes('locality')) acc.city = c.long_name;
                                        if (c.types.includes('administrative_area_level_1')) acc.state = c.long_name;
                                        if (c.types.includes('country')) acc.country = c.long_name;
                                    });
                                }
                            }
                            Object.entries(this.fill).forEach(([key, prop]) => {
                                const val = acc[key];
                                if (prop != null && val != null && val !== '') $wire.set(prop, key === 'latitude' || key === 'longitude' ? Number(val) : String(val));
                            });
                        }
                    }
                } catch (e) {
                    console.error('Place fetch failed:', e);
                }
            },
            close() {
                this.open = false;
            },
            getSuggestionLabel(suggestion) {
                try {
                    if (!suggestion) return '';
                    if (Array.isArray(suggestion) && suggestion[2] && typeof suggestion[2][0] === 'string') return suggestion[2][0];
                    const p = suggestion.placePrediction;
                    if (!p) return '';
                    if (p.text != null) {
                        if (typeof p.text === 'string') return p.text;
                        if (Array.isArray(p.text) && typeof p.text[0] === 'string') return p.text[0];
                        if (typeof p.text?.text === 'string') return p.text.text;
                        if (typeof p.text?.toString === 'function') return p.text.toString();
                    }
                    if (p.structuredFormat) {
                        const main = p.structuredFormat.mainText?.text ?? (Array.isArray(p.structuredFormat.mainText) ? p.structuredFormat.mainText[0] : p.structuredFormat.mainText);
                        const sec = p.structuredFormat.secondaryText?.text ?? (Array.isArray(p.structuredFormat.secondaryText) ? p.structuredFormat.secondaryText[0] : p.structuredFormat.secondaryText);
                        let t = typeof main === 'string' ? main : (main && main.toString ? main.toString() : '');
                        if (t && sec) {
                            const s = typeof sec === 'string' ? sec : (Array.isArray(sec) ? sec[0] : (sec && sec.toString ? sec.toString() : ''));
                            if (s) t = t + ', ' + s;
                        }
                        if (t) return t;
                    }
                    if (p.description != null) return typeof p.description === 'string' ? p.description : String(p.description);
                    return '';
                } catch (e) { return ''; }
            },
            suggestionText(suggestion) {
                try {
                    if (!suggestion) return '';
                    let t = '';
                    if (Array.isArray(suggestion) && suggestion[2] && typeof suggestion[2][0] === 'string') {
                        return suggestion[2][0];
                    }
                    const p = suggestion.placePrediction;
                    if (!p) return '';
                    if (p.text != null) {
                        if (typeof p.text === 'string') t = p.text;
                        else if (Array.isArray(p.text) && typeof p.text[0] === 'string') t = p.text[0];
                        else if (typeof p.text?.text === 'string') t = p.text.text;
                        else if (typeof p.text?.toString === 'function') t = p.text.toString();
                    }
                    if (!t && p.structuredFormat) {
                        const main = p.structuredFormat.mainText?.text ?? (Array.isArray(p.structuredFormat.mainText) ? p.structuredFormat.mainText[0] : p.structuredFormat.mainText);
                        const sec = p.structuredFormat.secondaryText?.text ?? (Array.isArray(p.structuredFormat.secondaryText) ? p.structuredFormat.secondaryText[0] : p.structuredFormat.secondaryText);
                        if (typeof main === 'string') t = main;
                        else if (main && typeof main.toString === 'function') t = main.toString();
                        if (t && sec) {
                            const s = typeof sec === 'string' ? sec : (Array.isArray(sec) ? sec[0] : (sec && sec.toString ? sec.toString() : ''));
                            if (s) t = t + ', ' + s;
                        }
                    }
                    if (!t && Array.isArray(p) && p[2] && typeof p[2][0] === 'string') t = p[2][0];
                    if (!t && p.description != null) t = typeof p.description === 'string' ? p.description : String(p.description);
                    return t || '';
                } catch (e) { return ''; }
            }
        }"
        x-init="$nextTick(() => {})"
        @click.away="close()"
        class="relative"
    >
        <flux:input
            x-ref="input"
            {{ $attributes->merge(['label' => $label, 'placeholder' => $placeholder]) }}
            x-on:input.debounce.300ms="fetchSuggestions()"
            x-on:focus="if ($refs.input.value.trim().length >= 2) fetchSuggestions()"
        />
        <div x-show="open && suggestions.length"
             x-transition
             class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-800 max-h-60 overflow-auto">
            <template x-for="(suggestion, i) in suggestions" :key="i">
                <button type="button"
                        @click="selectSuggestion(suggestion)"
                        class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700 focus:bg-zinc-100 dark:focus:bg-zinc-700 focus:outline-none"
                        x-text="suggestion.label">
                </button>
            </template>
        </div>
    </div>
@else
    <flux:input
        {{ $attributes->merge(['label' => $label, 'placeholder' => $placeholder]) }}
    />
@endif
