<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('dashboard') }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
            <flux:heading size="xl">{{ __('Schools') }}</flux:heading>
        </div>
        <flux:modal.trigger name="school-modal">
            <flux:button size="sm" variant="primary" icon="plus"><span class="hidden sm:inline">{{ __('New School') }}</span></flux:button>
        </flux:modal.trigger>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Official Name') }}</flux:table.column>
            <flux:table.column>{{ __('Address') }}</flux:table.column>
            <flux:table.column>{{ __('Campaign') }}</flux:table.column>
            <flux:table.column>{{ __('Magic Link') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($schools as $school)
                <flux:table.row :key="$school->id">
                    <flux:table.cell>
                        <flux:link href="{{ route('schools.structures', $school) }}" wire:navigate class="font-medium hover:underline">
                            {{ $school->official_name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ Str::limit($school->address, 30) }}</flux:table.cell>
                    <flux:table.cell>{{ $school->campaign?->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:tooltip content="{{ __('Copy Magic Link') }}">
                            <flux:button size="sm" variant="ghost" icon="link" wire:click="copyMagicLink('{{ $school->id }}')">
                                <span class="hidden sm:inline">{{ __('Copy Link') }}</span>
                            </flux:button>
                        </flux:tooltip>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit('{{ $school->id }}')">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item icon="building-office-2" href="{{ route('schools.structures', $school) }}" wire:navigate>{{ __('Manage Structures') }}</flux:menu.item>
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete('{{ $school->id }}')" wire:confirm="{{ __('Are you sure?') }}">
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="school-modal" class="w-4xl md:max-w-2xl lg:max-w-3xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $school_id ? __('Edit School') : __('New School') }}</flux:heading>
                <flux:subheading>{{ __('Manage schools and their details.') }}</flux:subheading>
            </div>

            <flux:separator :text="__('School info')" />
            <div class="grid grid-cols-[3fr_1fr] gap-4">
                <flux:input wire:model="official_name" label="{{ __('Official Name') }}" placeholder="{{ __('e.g. Scoala Gimnaziala Nr. 1') }}" />
                <flux:input wire:model="target_kits" label="{{ __('Target Kits') }}" type="number" min="0" />
            </div>
            <flux:select wire:model="campaign_id" label="{{ __('Campaign') }}" placeholder="Select campaign...">
                @foreach ($campaigns as $campaign)
                    <flux:select.option value="{{ $campaign->id }}">{{ $campaign->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:separator :text="__('Address')" />
            <x-address-autocomplete
                wire:model="address"
                :label="__('Address')"
                :placeholder="__('Start typing address...')"
                fill-city="city"
                fill-state="state"
                fill-country="country"
                fill-latitude="latitude"
                fill-longitude="longitude"
            />
            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="city" label="{{ __('City') }}" placeholder="{{ __('City / Oraș') }}" />
                <flux:input wire:model="state" label="{{ __('County') }}" placeholder="{{ __('County / Județ') }}" />
                <flux:input wire:model="country" label="{{ __('Country') }}" placeholder="Romania" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="latitude" label="{{ __('Latitude') }}" placeholder="45.9432" type="number" step="any" />
                <flux:input wire:model="longitude" label="{{ __('Longitude') }}" placeholder="24.9668" type="number" step="any" />
            </div>

            <flux:separator :text="__('Contact')" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="contact_person" label="{{ __('Contact Person') }}" placeholder="{{ __('Person to contact') }}" />
                <flux:input wire:model="contact_phone" label="{{ __('Contact Phone') }}" placeholder="{{ __('Phone number') }}" type="tel" />
            </div>

            <div class="flex pt-2">
                <flux:spacer />
                <flux:button size="sm" type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

@script
<script>
    $wire.on('copy-to-clipboard', (event) => {
        if (event.url && navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(event.url);
        }
    });
</script>
@endscript
