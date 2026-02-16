<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            @if(isset($campaign) && $campaign)
                <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('campaigns.schools', $campaign) }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
            @else
                <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('schools') }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
            @endif
            <flux:heading size="xl">{{ __('Structures') }}</flux:heading>
        </div>
        <flux:modal.trigger name="structure-modal">
            <flux:button size="sm" variant="primary" icon="plus"><span class="hidden sm:inline">{{ __('New Structure') }}</span></flux:button>
        </flux:modal.trigger>
    </div>

    <flux:card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg">{{ $school->official_name }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $school->address }}</flux:text>
                @if ($school->contact_person || $school->contact_phone || $school->contact_email)
                    <flux:text class="text-sm">
                        @if ($school->contact_person){{ $school->contact_person }}@endif
                        @if ($school->contact_person && ($school->contact_phone || $school->contact_email)) · @endif
                        @if ($school->contact_phone){{ $school->contact_phone }}@endif
                        @if ($school->contact_phone && $school->contact_email) · @endif
                        @if ($school->contact_email)<a href="mailto:{{ $school->contact_email }}" class="text-zinc-600 dark:text-zinc-400 hover:underline">{{ $school->contact_email }}</a>@endif
                    </flux:text>
                @endif
                <flux:text class="text-sm">{{ __('Campaign') }}: {{ $school->campaign?->name }}</flux:text>
            </div>
            <flux:tooltip content="{{ __('Copy portal link for educators') }}">
                <flux:button size="sm" variant="primary" icon="link" wire:click="copyPortalLink">
                    {{ __('Copy portal link') }}
                </flux:button>
            </flux:tooltip>
        </div>
        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-wrap gap-4">
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $structures_count }}</span>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Structures') }}</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $groups_count }}</span>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Groups') }}</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $children_count }}</span>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Children') }}</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $total_target_kits }}</span>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Target kits') }}</span>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">{{ __('Retur / Primire din retur') }}</flux:heading>
        <flux:subheading>{{ __('When the actual number of children differs from the order: record kits returned (retur) or received from return (primire din retur), then generate the official documents.') }}</flux:subheading>
        <form wire:submit="saveReturPrimire" class="mt-4 flex flex-wrap items-end gap-4">
            <flux:input wire:model="kits_returned" type="number" min="0" label="{{ __('Kits returned (Retur)') }}" placeholder="0" class="w-32" />
            <flux:input wire:model="kits_received_from_return" type="number" min="0" label="{{ __('Kits received from return (Primire din retur)') }}" placeholder="0" class="w-32" />
            <flux:button type="submit" size="sm" variant="primary">{{ __('Save') }}</flux:button>
        </form>
        <div class="mt-4 flex flex-wrap gap-2">
            <flux:button size="sm" variant="outline" href="{{ route('schools.docs.proces-verbal-retur', $school) }}" target="_blank">
                {{ __('Generate Proces verbal de Retur') }}
            </flux:button>
            <flux:button size="sm" variant="outline" href="{{ route('schools.docs.proces-verbal-primire-din-retur', $school) }}" target="_blank">
                {{ __('Generate Proces verbal de primire din retur') }}
            </flux:button>
        </div>
    </flux:card>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Address') }}</flux:table.column>
            <flux:table.column>{{ __('Target Kits') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($structures as $structure)
                <flux:table.row :key="$structure->id">
                    <flux:table.cell>{{ $structure->name }}</flux:table.cell>
                    <flux:table.cell>{{ $structure->same_location_as_school ? __('Same as school') : ($structure->address ? Str::limit($structure->address, 50) : '—') }}</flux:table.cell>
                    <flux:table.cell>{{ $structure->target_kits ?? 0 }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit('{{ $structure->id }}')">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete('{{ $structure->id }}')" wire:confirm="{{ __('Are you sure?') }}">
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    @if ($structures->isEmpty())
        <flux:card>
            <div class="text-center py-12 text-zinc-500">
                <flux:heading size="lg">{{ __('No structures yet.') }}</flux:heading>
                <flux:text>{{ __('Add structures (e.g. kindergartens, satellite locations) for this school.') }}</flux:text>
            </div>
        </flux:card>
    @endif

    <flux:modal name="structure-modal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $structure_id ? __('Edit Structure') : __('New Structure') }}</flux:heading>
                <flux:subheading>{{ __('Educators will set the location in the portal.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="{{ __('Name') }}" placeholder="e.g. Grădinița cu Program Prelungit Nr. 4" />
            <flux:input wire:model="target_kits" label="{{ __('Target Kits') }}" type="number" min="0" />
            <flux:checkbox wire:model="same_location_as_school" label="{{ __('Same location as school') }}" description="{{ __('Use school address; educators do not need to set a separate location.') }}" />

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
