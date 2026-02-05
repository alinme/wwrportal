<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('school.dashboard', $school) }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
            <div>
                <flux:heading size="xl">{{ $structure->name }}</flux:heading>
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item href="{{ route('school.dashboard', $school) }}" wire:navigate>{{ $school->official_name }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $structure->name }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
        </div>
        <flux:button size="sm" variant="primary" icon="plus" wire:click="openAddGroupModal"><span class="hidden sm:inline">{{ __('Add Group') }}</span></flux:button>
    </div>

    <flux:card>
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">{{ __('Structure location') }}</flux:heading>
                <flux:text>{{ $structure->address ?: __('Not set — only you know the true address') }}</flux:text>
            </div>
            <flux:button size="sm" variant="ghost" icon="map-pin" wire:click="openStructureLocationModal">
                {{ $structure->address ? __('Update location') : __('Set location') }}
            </flux:button>
        </div>
    </flux:card>

    <div class="space-y-6">
        <div class="grid gap-6">
            @foreach ($groups as $group)
                @php
                    $groupChildrenCount = $group->children->count();
                    $groupTarget = (int) ($group->target_kits ?? 0);
                    $groupReady = $groupTarget > 0 ? $groupChildrenCount >= $groupTarget : $groupChildrenCount > 0;
                @endphp
                <flux:card class="{{ $groupReady ? 'ring-2 ring-green-500' : '' }}">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">{{ $group->name }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">{{ $group->educator_name }}{{ $group->contact_phone ? ' • ' . $group->contact_phone : '' }}</flux:text>
                            <flux:badge :color="$groupReady ? 'green' : 'red'" class="mt-1">{{ $groupReady ? __('Ready') : __('Empty') }} ({{ $groupChildrenCount }}/{{ $groupTarget ?: '—' }})</flux:badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="document-text"><span class="hidden sm:inline">{{ __('Generate Table') }}</span></flux:button>
                                <flux:menu>
                                    <flux:menu.item icon="eye" href="{{ route('school.docs.group.distribution', [$school, $group]) }}?preview=1" target="_blank">
                                        {{ __('Preview') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="arrow-down-tray" href="{{ route('school.docs.group.distribution', [$school, $group]) }}" target="_blank">
                                        {{ __('Download PDF') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                            <flux:button size="sm" icon="plus" wire:click="openAddChildModal('{{ $group->id }}')"><span class="hidden sm:inline">{{ __('Add Child') }}</span></flux:button>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="editGroup('{{ $group->id }}')">{{ __('Edit Group') }}</flux:menu.item>
                                    <flux:menu.item icon="trash" variant="danger" wire:click="deleteGroup('{{ $group->id }}')" wire:confirm="{{ __('Are you sure? This will delete all children in this group.') }}">
                                        {{ __('Delete Group') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Child Name') }}</flux:table.column>
                            <flux:table.column>{{ __('Parent Name') }}</flux:table.column>
                            <flux:table.column class="w-24">{{ __('Actions') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($group->children as $child)
                                <flux:table.row :key="$child->id">
                                    <flux:table.cell>{{ $child->child_full_name }}</flux:table.cell>
                                    <flux:table.cell>{{ $child->parent_full_name }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:dropdown>
                                            <flux:button size="sm" variant="ghost" icon="shield-check" title="{{ __('GDPR') }}" />
                                            <flux:menu>
                                                <flux:menu.item icon="eye" href="{{ route('school.docs.gdpr.child', [$school, $child]) }}?with_parent_names=1&preview=1" target="_blank">
                                                    {{ __('Preview') }}
                                                </flux:menu.item>
                                                <flux:menu.item icon="arrow-down-tray" href="{{ route('school.docs.gdpr.child', [$school, $child]) }}?with_parent_names=1" target="_blank">
                                                    {{ __('Download') }}
                                                </flux:menu.item>
                                                <flux:menu.item icon="share" wire:click="copyGdprShareMessage('{{ $child->id }}')">
                                                    {{ __('Copy message for parent') }}
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editChild('{{ $child->id }}')" title="{{ __('Edit') }}" />
                                        <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteChild('{{ $child->id }}')" wire:confirm="{{ __('Are you sure?') }}" title="{{ __('Delete') }}" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                            @if ($group->children->isEmpty())
                                <flux:table.row>
                                    <flux:table.cell colspan="3" class="text-center text-zinc-500">{{ __('No children added yet.') }}</flux:table.cell>
                                </flux:table.row>
                            @endif
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            @endforeach
        </div>
    </div>

    <flux:modal name="add-group-modal" class="md:w-96">
        <form wire:submit="createGroup" class="space-y-6">
            <flux:heading size="lg">{{ $group_id ? __('Edit Group') : __('Add Group') }}</flux:heading>

            <flux:input wire:model="group_name" label="{{ __('Class Name') }}" placeholder="e.g. Grupa Mare" required />
            <flux:input wire:model="educator_name" label="{{ __('Educator / Teacher') }}" placeholder="e.g. Maria Progoana" required />
            <flux:input wire:model="contact_phone" label="{{ __('Contact Phone') }}" placeholder="{{ __('Educator phone number') }}" type="tel" />
            <flux:input wire:model="target_kits" label="{{ __('Target Kits') }}" type="number" min="0" />

            <div class="flex">
                <flux:spacer />
                <flux:button size="sm" type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="structure-location-modal" class="md:max-w-2xl lg:max-w-4xl">
        <form wire:submit.prevent class="space-y-6" x-data="{
            saveLocation() {
                let lat = null, lng = null;
                if (typeof window !== 'undefined' && window.__structureMapPosition) {
                    lat = window.__structureMapPosition.lat;
                    lng = window.__structureMapPosition.lng;
                }
                if (lat == null && $wire.structure_latitude != null) lat = $wire.structure_latitude;
                if (lng == null && $wire.structure_longitude != null) lng = $wire.structure_longitude;
                $wire.saveStructureLocation(lat, lng);
            }
        }">
            <div>
                <flux:heading size="lg">{{ __('Set structure location') }}</flux:heading>
                <flux:subheading>{{ __('You know the exact address. Search or drag the pin on the map.') }}</flux:subheading>
            </div>

            <input type="hidden" wire:model="structure_latitude" />
            <input type="hidden" wire:model="structure_longitude" />
            <x-address-autocomplete
                wire:model="structure_address"
                model="structure_address"
                :label="__('Address')"
                :placeholder="__('Start typing address...')"
                fill-latitude="structure_latitude"
                fill-longitude="structure_longitude"
            />
            <x-address-map-picker
                :latitude="$structure_latitude"
                :longitude="$structure_longitude"
                height="180px"
                wire-address="structure_address"
                wire-latitude="structure_latitude"
                wire-longitude="structure_longitude"
            />

            <div class="flex pt-2">
                <flux:spacer />
                <flux:button size="sm" type="button" variant="primary" @click="saveLocation()">{{ __('Save location') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="add-child-modal" class="md:max-w-xl lg:max-w-2xl">
        <form wire:submit.prevent class="space-y-6">
            <flux:heading size="lg">{{ $child_id ? __('Edit Child') : __('Add Child') }}</flux:heading>

            <flux:input wire:model="child_full_name" label="{{ __('Child Name') }}" placeholder="{{ __('e.g. Maria Popescu') }}" required />
            <flux:input wire:model="parent_full_name" label="{{ __('Parent Name') }}" placeholder="{{ __('e.g. Ion Popescu') }}" required />

            <flux:separator :text="__('Parent details (optional, for GDPR form)')" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="parent_locality" label="{{ __('Locality') }}" placeholder="{{ __('e.g. București') }}" />
                <flux:input wire:model="parent_county" label="{{ __('County') }}" placeholder="{{ __('e.g. București') }}" />
                <flux:input wire:model="parent_birth_date" label="{{ __('Parent birth date') }}" type="date" />
                <flux:input wire:model="child_birth_date" label="{{ __('Child birth date') }}" type="date" />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                @if(!$child_id)
                    <flux:button size="sm" type="button" variant="ghost" wire:click="saveChild(true)">{{ __('Save and Add Another') }}</flux:button>
                @endif
                <flux:button size="sm" type="button" variant="primary" wire:click="saveChild(false)">{{ $child_id ? __('Update') : __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

@script
<script>
    $wire.on('copy-to-clipboard', (event) => {
        const text = event.text || event.url || '';
        if (text && navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(text);
        }
    });
</script>
@endscript
