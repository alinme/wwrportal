<div class="flex flex-col gap-6">
    <div class="text-center space-y-2">
        <flux:heading size="xl">{{ $school->official_name }}</flux:heading>
        <flux:subheading>{{ $school->address }}</flux:subheading>
        @if($school->contact_person || $school->contact_phone)
            <flux:text class="text-sm text-zinc-500">
                {{ $school->contact_person }}{{ $school->contact_person && $school->contact_phone ? ' • ' : '' }}{{ $school->contact_phone }}
            </flux:text>
        @endif

        <div class="flex flex-wrap gap-2 justify-center mt-4">
            <flux:button size="sm" icon="document-text" href="{{ route('school.docs.contract', $school) }}" target="_blank"><span class="hidden sm:inline">{{ __('Contract') }}</span></flux:button>
            <flux:button size="sm" icon="clipboard-document-list" href="{{ route('school.docs.annex', $school) }}" target="_blank"><span class="hidden sm:inline">{{ __('Annex') }}</span></flux:button>
            <flux:dropdown>
                <flux:button size="sm" icon="shield-check"><span class="hidden sm:inline">{{ __('GDPR Forms') }}</span></flux:button>
                <flux:menu>
                    <flux:menu.item icon="eye" href="{{ route('school.docs.gdpr', $school) }}?with_parent_names=1&preview=1" target="_blank">
                        {{ __('Preview') }} ({{ __('With parent names') }})
                    </flux:menu.item>
                    <flux:menu.item icon="user" href="{{ route('school.docs.gdpr', $school) }}?with_parent_names=1" target="_blank">
                        {{ __('Download') }} ({{ __('With parent names') }})
                    </flux:menu.item>
                    <flux:menu.item icon="eye" href="{{ route('school.docs.gdpr', $school) }}?with_parent_names=0&preview=1" target="_blank">
                        {{ __('Preview') }} ({{ __('Without parent names (for parents to write)') }})
                    </flux:menu.item>
                    <flux:menu.item icon="pencil-square" href="{{ route('school.docs.gdpr', $school) }}?with_parent_names=0" target="_blank">
                        {{ __('Download') }} ({{ __('Without parent names (for parents to write)') }})
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <flux:separator />

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($structures as $structure)
            @php
                $structureChildrenCount = $structure->groups->sum(fn ($g) => $g->children->count());
                $structureTarget = (int) ($structure->target_kits ?? 0);
                $structureReady = $structureTarget > 0 ? $structureChildrenCount >= $structureTarget : $structureChildrenCount > 0;
            @endphp
            <flux:card class="space-y-4 {{ $structureReady ? 'ring-2 ring-green-500' : '' }}">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ $structure->name }}</flux:heading>
                    <flux:badge :color="$structureReady ? 'green' : 'red'">{{ $structureReady ? __('Ready') : __('Empty') }}</flux:badge>
                </div>
                <flux:text>{{ $structure->address ?: __('Location not set — set it when managing groups') }}</flux:text>
                <flux:text class="text-sm text-zinc-500">{{ $structureChildrenCount }} / {{ $structureTarget ?: '—' }} {{ __('kits') }}</flux:text>

                <flux:button size="sm" class="w-full" icon="arrow-right" wire:navigate href="{{ route('school.structure', [$school, $structure]) }}">
                    {{ __('Manage Groups') }}
                </flux:button>
            </flux:card>
        @endforeach

        @if($structures->isEmpty())
            <div class="col-span-full text-center py-12">
                <flux:heading size="lg">{{ __('No structures definition found.') }}</flux:heading>
                <flux:text>{{ __('Please wait for the administrator to define the school structure.') }}</flux:text>
            </div>
        @endif
    </div>
</div>
