<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Foundation Settings') }}</flux:heading>
        <flux:subheading>{{ __('Update foundation details and branding.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="name" label="{{ __('Foundation Name') }}" />
        <flux:textarea wire:model="details" label="{{ __('Details / Description') }}" />
        <flux:input wire:model="logo_path" label="{{ __('Logo URL') }}" />

        <div class="flex">
            <flux:spacer />
            <flux:button size="sm" type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>
</div>
