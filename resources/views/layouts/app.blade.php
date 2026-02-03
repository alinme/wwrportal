<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
    <flux:toast />
</x-layouts::app.sidebar>
