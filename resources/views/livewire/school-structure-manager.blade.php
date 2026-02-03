<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('schools') }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
            <flux:heading size="xl">{{ __('Structures') }}</flux:heading>
        </div>
        <flux:modal.trigger name="structure-modal">
            <flux:button size="sm" variant="primary" icon="plus"><span class="hidden sm:inline">{{ __('New Structure') }}</span></flux:button>
        </flux:modal.trigger>
    </div>

    <flux:card>
        <div class="space-y-2">
            <flux:heading size="lg">{{ $school->official_name }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $school->address }}</flux:text>
            @if ($school->contact_person || $school->contact_phone)
                <flux:text class="text-sm">
                    @if ($school->contact_person){{ $school->contact_person }}@endif
                    @if ($school->contact_person && $school->contact_phone) · @endif
                    @if ($school->contact_phone){{ $school->contact_phone }}@endif
                </flux:text>
            @endif
            <flux:text class="text-sm">{{ __('Campaign') }}: {{ $school->campaign?->name }}</flux:text>
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
                    <flux:table.cell>{{ $structure->address ? Str::limit($structure->address, 50) : '—' }}</flux:table.cell>
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

            <div class="flex pt-2">
                <flux:spacer />
                <flux:button size="sm" type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
