<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:heading size="xl">{{ __('Schools Map') }}</flux:heading>
        <livewire:schools-map />
    </div>
</x-layouts::app>
