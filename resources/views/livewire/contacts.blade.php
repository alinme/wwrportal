<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Contacts') }}</flux:heading>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button size="sm" variant="outline" icon="arrow-path" wire:click="syncFromSchools" wire:loading.attr="disabled">
                <span class="hidden sm:inline">{{ __('Sync from schools') }}</span>
                <span wire:loading class="ms-1">{{ __('Syncing…') }}</span>
            </flux:button>
            <flux:button size="sm" variant="primary" icon="plus" wire:click="openNew">
                <span class="hidden sm:inline">{{ __('Add Contact') }}</span>
            </flux:button>
        </div>
    </div>

    <flux:input
        wire:model.live.debounce.300ms="search"
        :placeholder="__('Search by name, email, phone, organization or notes...')"
        icon="magnifying-glass"
        class="max-w-md"
    />

    @if($contacts->isEmpty())
        <flux:card>
            <div class="text-center py-12 text-zinc-500">
                <flux:heading size="lg">{{ $search ? __('No contacts match your search.') : __('No contacts yet.') }}</flux:heading>
                <flux:text>{{ $search ? __('Try a different search or add a new contact.') : __('Add a contact, sync from schools, or import from Excel in Uploads.') }}</flux:text>
                @if(!$search)
                    <div class="mt-4 flex flex-wrap gap-2 justify-center">
                        <flux:button size="sm" variant="outline" icon="arrow-path" wire:click="syncFromSchools" wire:loading.attr="disabled">{{ __('Sync from schools') }}</flux:button>
                        <flux:button size="sm" variant="primary" icon="plus" wire:click="openNew">{{ __('Add Contact') }}</flux:button>
                    </div>
                @endif
            </div>
        </flux:card>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Phone') }}</flux:table.column>
                <flux:table.column>{{ __('Organization') }}</flux:table.column>
                <flux:table.column>{{ __('Notes') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($contacts as $contact)
                    <flux:table.row :key="$contact->id">
                        <flux:table.cell class="font-medium">{{ $contact->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if($contact->email)
                                <a href="mailto:{{ $contact->email }}" class="text-zinc-600 dark:text-zinc-400 hover:underline">{{ $contact->email }}</a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($contact->phone)
                                <a href="tel:{{ $contact->phone }}" class="text-zinc-600 dark:text-zinc-400 hover:underline">{{ $contact->phone }}</a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $contact->organization ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs">
                            <span class="line-clamp-2 text-zinc-600 dark:text-zinc-400" title="{{ $contact->notes }}">{{ $contact->notes ? Str::limit($contact->notes, 60) : '—' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="edit('{{ $contact->id }}')">{{ __('Edit') }}</flux:menu.item>
                                    <flux:menu.item icon="trash" variant="danger" wire:click="delete('{{ $contact->id }}')" wire:confirm="{{ __('Are you sure?') }}">
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

    <flux:modal name="contact-modal" class="w-4xl md:max-w-xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit Contact') : __('Add Contact') }}</flux:heading>
                <flux:subheading>{{ __('Use notes to remember details about this contact.') }}</flux:subheading>
            </div>

            <flux:input wire:model="edit_name" :label="__('Name')" :placeholder="__('Full name')" />
            <flux:input wire:model="edit_email" type="email" :label="__('Email')" placeholder="email@example.com" />
            <flux:input wire:model="edit_phone" :label="__('Phone')" :placeholder="__('Phone number')" />
            <flux:input wire:model="edit_organization" :label="__('Organization')" :placeholder="__('Company or organization')" />
            <flux:textarea wire:model="edit_notes" :label="__('Notes')" :placeholder="__('Optional notes – e.g. when to call, preferences')" rows="4" />

            <div class="flex pt-2">
                <flux:spacer />
                <flux:button size="sm" type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
