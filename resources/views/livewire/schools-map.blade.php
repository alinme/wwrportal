<div class="flex flex-col gap-4" x-data="schoolsMap()">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <div class="flex items-center gap-4">
        <flux:badge color="green">{{ __('Ready') }} ({{ $schools->where('status', 'ready')->count() }})</flux:badge>
        <flux:badge color="red">{{ __('Empty') }} ({{ $schools->where('status', 'empty')->count() }})</flux:badge>
    </div>

    <div
        id="schools-map"
        class="h-[500px] w-full rounded-xl border border-neutral-200 dark:border-neutral-700"
        x-init="init()"
    ></div>

    @if($schools->whereNotNull('latitude')->isEmpty())
        <flux:callout variant="warning">
            {{ __('No school coordinates. Add latitude/longitude to schools to display them on the map.') }}
        </flux:callout>
    @endif

    @once
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endonce

    @once
    <script>
        function schoolsMap() {
        return {
            schools: @js($schools),
            map: null,

            init() {
                this.$nextTick(() => {
                    if (typeof L === 'undefined') return;

                    const container = document.getElementById('schools-map');
                    if (!container) return;

                    const existingMap = window._schoolsMapInstance;
                    if (existingMap) {
                        existingMap.remove();
                        window._schoolsMapInstance = null;
                    }

                    this.map = L.map('schools-map').setView([45.9, 25.0], 6);
                    window._schoolsMapInstance = this.map;

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(this.map);

                    const schoolsWithCoords = this.schools.filter(s => s.latitude && s.longitude);

                    schoolsWithCoords.forEach(school => {
                        const color = school.status === 'ready' ? '#22c55e' : '#ef4444';
                        const marker = L.circleMarker([school.latitude, school.longitude], {
                            radius: 10,
                            fillColor: color,
                            color: '#fff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.8,
                        }).addTo(this.map);

                        marker.bindPopup(
                            '<b>' + school.name + '</b><br>' +
                            school.address + '<br>' +
                            '<span style="color:' + color + '">' + (school.status === 'ready' ? '{{ __('Ready') }}' : '{{ __('Empty') }}') + '</span>'
                        );
                    });
                });
            }
        };
    }
    </script>
    @endonce
</div>
