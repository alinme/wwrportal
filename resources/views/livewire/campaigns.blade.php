<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('dashboard') }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
            <flux:heading size="xl">{{ __('Campaigns') }}</flux:heading>
        </div>
        <flux:modal.trigger name="campaign-modal">
            <flux:button size="sm" variant="primary" icon="plus"><span class="hidden sm:inline">{{ __('New Campaign') }}</span></flux:button>
        </flux:modal.trigger>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Facilitator') }}</flux:table.column>
            <flux:table.column>{{ __('Suffix') }}</flux:table.column>
            <flux:table.column>{{ __('Target') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($campaigns as $campaign)
                <flux:table.row :key="$campaign->id">
                    <flux:table.cell>{{ $campaign->name }}</flux:table.cell>
                    <flux:table.cell>{{ $campaign->facilitator_name }}</flux:table.cell>
                    <flux:table.cell>{{ $campaign->month_year_suffix }}</flux:table.cell>
                    <flux:table.cell>{{ $campaign->target_kits }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$campaign->is_active ? 'green' : 'red'">
                            {{ $campaign->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit({{ $campaign->id }})">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $campaign->id }})" wire:confirm="{{ __('Are you sure?') }}">
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="campaign-modal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $campaign_id ? __('Edit Campaign') : __('New Campaign') }}</flux:heading>
                <flux:subheading>{{ __('Manage distribution campaigns.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="{{ __('Campaign Name') }}" placeholder="e.g. Brasov - March 2026" />
            <flux:input wire:model="facilitator_name" label="{{ __('Facilitator') }}" />
            <flux:input wire:model="month_year_suffix" label="{{ __('Month/Year Suffix') }}" placeholder="e.g. .03.2026" />
            <flux:input wire:model="target_kits" type="number" label="{{ __('Target Kits') }}" />
            
            <flux:checkbox wire:model="is_active" label="{{ __('Active') }}" />

            <div class="flex">
                <flux:spacer />
                <flux:button size="sm" type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
