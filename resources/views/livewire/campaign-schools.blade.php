<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('campaigns') }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
            <flux:heading size="xl">{{ $campaign->name }}</flux:heading>
            <flux:badge color="zinc">{{ __('Schools') }}</flux:badge>
        </div>
        <flux:button size="sm" variant="primary" icon="plus" wire:click="openNewSchoolModal"><span class="hidden sm:inline">{{ __('New School') }}</span></flux:button>
    </div>

    @if($schools->isEmpty())
        <flux:card>
            <div class="text-center py-12 text-zinc-500">
                <flux:heading size="lg">{{ __('No schools in this campaign') }}</flux:heading>
                <flux:text>{{ __('Add a school to get started.') }}</flux:text>
                <div class="mt-4">
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="openNewSchoolModal">{{ __('Add school') }}</flux:button>
                </div>
            </div>
        </flux:card>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Official Name') }}</flux:table.column>
                <flux:table.column>{{ __('Address') }}</flux:table.column>
                <flux:table.column>{{ __('Magic Link') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($schools as $school)
                    <flux:table.row :key="$school->id">
                        <flux:table.cell>
                            <flux:link href="{{ route('campaigns.schools.structures', [$campaign, $school]) }}" wire:navigate class="font-medium hover:underline">
                                {{ $school->official_name }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ Str::limit($school->address, 30) }}</flux:table.cell>
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
                                    <flux:menu.item icon="building-office-2" href="{{ route('campaigns.schools.structures', [$campaign, $school]) }}" wire:navigate>{{ __('Manage Structures') }}</flux:menu.item>
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
    @endif

    <flux:modal name="school-modal" class="w-4xl md:max-w-2xl lg:max-w-3xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $school_id ? __('Edit School') : __('New School') }}</flux:heading>
                <flux:subheading>{{ __('School will be added to campaign :name.', ['name' => $campaign->name]) }}</flux:subheading>
            </div>

            <flux:separator :text="__('Select from database')" />
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <flux:input
                    wire:model.live.debounce.300ms="schoolSearch"
                    :label="__('Search existing schools')"
                    :placeholder="__('Type school name, address or city...')"
                    x-on:focus="open = true"
                />
                @if(count($schoolSearchResults) > 0)
                    <div class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-800 max-h-60 overflow-auto">
                        @foreach($schoolSearchResults as $school)
                            <button
                                type="button"
                                wire:click="selectSchoolFromSearch('{{ $school->id }}')"
                                class="flex w-full flex-col gap-0.5 px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700 focus:bg-zinc-100 dark:focus:bg-zinc-700 focus:outline-none"
                            >
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $school->official_name }}</span>
                                @if($school->address)
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ Str::limit($school->address, 50) }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <flux:separator :text="__('School info')" />
            <div class="grid grid-cols-[3fr_1fr] gap-4">
                <flux:input wire:model="official_name" label="{{ __('Official Name') }}" placeholder="{{ __('e.g. Scoala Gimnaziala Nr. 1') }}" />
                <flux:input wire:model="target_kits" label="{{ __('Target Kits') }}" type="number" min="0" />
            </div>

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
                <flux:input wire:model="contact_email" label="{{ __('Contact email') }}" placeholder="email@example.com" type="email" class="sm:col-span-2" />
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
