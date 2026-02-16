<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('dashboard') }}" wire:navigate><span class="hidden sm:inline">{{ __('Back') }}</span></flux:button>
        <flux:heading size="xl">{{ __('Uploads & Import') }}</flux:heading>
    </div>

    <div class="flex gap-2 p-1 rounded-lg bg-zinc-800/5 dark:bg-white/10 w-fit" role="tablist">
        <button type="button" role="tab" wire:click="$set('activeTab', 'schools')"
                class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $activeTab === 'schools' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
            {{ __('Import Schools') }}
        </button>
        <button type="button" role="tab" wire:click="$set('activeTab', 'contacts')"
                class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $activeTab === 'contacts' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
            {{ __('Import Contacts') }}
        </button>
        <button type="button" role="tab" wire:click="$set('activeTab', 'manual-contact')"
                class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $activeTab === 'manual-contact' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
            {{ __('Add Contact') }}
        </button>
    </div>

    {{-- Manual contact form --}}
    @if($activeTab === 'manual-contact')
        <flux:card class="max-w-xl">
            <form wire:submit="saveManualContact" class="space-y-4">
                <flux:heading size="lg">{{ __('Add a contact manually') }}</flux:heading>
                <flux:subheading>{{ __('Contacts can be used later for autocomplete.') }}</flux:subheading>
                <flux:input wire:model="contact_name" label="{{ __('Name') }}" placeholder="{{ __('Full name') }}" />
                <flux:input wire:model="contact_email" type="email" label="{{ __('Email') }}" placeholder="email@example.com" />
                <flux:input wire:model="contact_phone" label="{{ __('Phone') }}" placeholder="{{ __('Phone number') }}" />
                <flux:input wire:model="contact_organization" label="{{ __('Organization') }}" placeholder="{{ __('Company or organization') }}" />
                <flux:textarea wire:model="contact_notes" label="{{ __('Notes') }}" placeholder="{{ __('Optional notes') }}" rows="2" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" size="sm">{{ __('Save contact') }}</flux:button>
                </div>
            </form>
        </flux:card>
        <flux:text class="text-zinc-500">{{ __(':count contact(s) in database.', ['count' => $contacts_count]) }}</flux:text>
    @endif

    {{-- Import wizard: Schools or Contacts --}}
    @if($activeTab === 'schools' || $activeTab === 'contacts')
        @if($step === 1)
            <flux:card class="max-w-xl">
                <flux:heading size="lg">{{ $importType === 'schools' ? __('Import schools from Excel') : __('Import contacts from Excel') }}</flux:heading>
                <flux:subheading>{{ __('Upload an .xlsx or .xls file. You will then choose the sheet, header row, and map columns.') }}</flux:subheading>
                <div class="mt-4">
                    <label class="flex flex-col items-center justify-center w-full py-10 px-6 rounded-lg border-2 border-dashed border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-white/10 hover:bg-zinc-100 dark:hover:bg-white/15 transition-colors cursor-pointer">
                        <flux:icon icon="arrow-up-tray" class="size-10 text-zinc-400 mb-2" />
                        <span class="text-sm font-medium text-zinc-800 dark:text-white">{{ __('Drop file here or click to browse') }}</span>
                        <span class="text-xs text-zinc-500 mt-1">{{ __('.xlsx, .xls, .csv up to 10 MB') }}</span>
                        <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" class="hidden" />
                    </label>
                    <div wire:loading class="mt-2 text-sm text-zinc-500">
                        {{ __('Uploading…') }}
                    </div>
                </div>
                <flux:error name="file" class="mt-2" />
            </flux:card>
        @endif

        @if($step >= 2 && $storedPath)
            <flux:card>
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <flux:badge color="zinc">Step {{ $step }}/6</flux:badge>
                    <flux:button size="sm" variant="ghost" wire:click="startOver">{{ __('Start over') }}</flux:button>
                </div>

                @if($step === 2)
                    <flux:heading size="lg">{{ __('Select sheet') }}</flux:heading>
                    <flux:subheading>{{ __('Which sheet contains the data? Sheets are often named after the Județ (county) – this will be used as default for County.') }}</flux:subheading>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($sheetNames as $idx => $name)
                            <flux:button size="sm" variant="{{ $sheetIndex === $idx ? 'primary' : 'outline' }}" wire:click="setSheet({{ $idx }})">
                                {{ $name }}
                            </flux:button>
                        @endforeach
                    </div>
                @endif

                @if($step === 3)
                    <flux:heading size="lg">{{ __('Select header row') }}</flux:heading>
                    <flux:subheading>{{ __('Which row number contains the column titles? (Data rows below this will be imported.)') }}</flux:subheading>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @for($r = 1; $r <= min($totalRows, 20); $r++)
                            <flux:button size="sm" variant="{{ $headerRow === $r ? 'primary' : 'outline' }}" wire:click="setHeaderRow({{ $r }})">
                                {{ __('Row :n', ['n' => $r]) }}
                            </flux:button>
                        @endfor
                    </div>
                    @if($totalRows > 20)
                        <flux:input type="number" min="1" max="{{ $totalRows }}" wire:model.live="headerRow" label="{{ __('Or enter row number') }}" class="mt-4 max-w-xs" />
                    @endif
                @endif

                @if($step === 4)
                    <flux:heading size="lg">{{ __('Map columns') }}</flux:heading>
                    <flux:subheading>{{ __('For each field, select the Excel column (or leave unset to use default). Country is always Romania. County (Județ) was pre-filled from the sheet name if applicable.') }}</flux:subheading>
                    @if($importType === 'schools')
                        <flux:separator :text="__('Default campaign for imported schools')" class="mt-4" />
                        <select wire:model.live="defaultCampaignId" class="max-w-sm mb-4 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach($campaigns as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="py-2 pr-4 text-left text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Our field') }}</th>
                                    <th class="py-2 text-left text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Excel column') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importType === 'schools' ? $this->getSchoolMappingFields() : $this->getContactMappingFields() as $key => $label)
                                    <tr>
                                        <td class="py-2 pr-4 text-sm">{{ $label }}</td>
                                        <td class="py-2">
                                            <select wire:model.live="columnMapping.{{ $key }}" class="min-w-[140px] rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                                                <option value="">{{ __('— Skip —') }}</option>
                                                @foreach($excelColumns as $colIdx => $colLabel)
                                                    <option value="{{ $colIdx }}">{{ $colLabel }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <flux:button size="sm" variant="primary" wire:click="goToPreview">{{ __('Next: Filters & preview') }}</flux:button>
                    </div>
                @endif

                @if($step === 5)
                    <flux:heading size="lg">{{ __('Filters (optional)') }}</flux:heading>
                    <flux:subheading>{{ __('Include only rows that match these conditions.') }}</flux:subheading>
                    <div class="mt-4 space-y-3">
                        @foreach($filters as $idx => $f)
                            <div class="flex flex-wrap items-center gap-2">
                                <select wire:model.live="filters.{{ $idx }}.col" class="w-32 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                                    @foreach($excelColumns as $colIdx => $colLabel)
                                        <option value="{{ $colIdx }}">{{ $colLabel }}</option>
                                    @endforeach
                                </select>
                                <select wire:model.live="filters.{{ $idx }}.op" class="w-40 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                                    @foreach($this->filterOperators() as $opVal => $opLabel)
                                        <option value="{{ $opVal }}">{{ $opLabel }}</option>
                                    @endforeach
                                </select>
                                <flux:input wire:model.live="filters.{{ $idx }}.value" placeholder="{{ __('Value') }}" class="flex-1 min-w-[120px]" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeFilter({{ $idx }})" />
                            </div>
                        @endforeach
                        <flux:button size="sm" variant="outline" icon="plus" wire:click="addFilter">{{ __('Add filter') }}</flux:button>
                    </div>
                    <div class="mt-6 flex gap-4 items-center">
                        <flux:button size="sm" variant="primary" wire:click="buildPreview">{{ __('Update preview') }}</flux:button>
                        <flux:text class="text-zinc-500">{{ __(':count row(s) after filters', ['count' => $totalFiltered]) }}</flux:text>
                    </div>

                    <flux:separator :text="__('Preview (first 50 rows)')" class="mt-6" />
                    <div class="overflow-x-auto mt-2">
                        <flux:table>
                            <flux:table.columns>
                                @foreach($importType === 'schools' ? $this->getSchoolMappingFields() : $this->getContactMappingFields() as $key => $label)
                                    <flux:table.column>{{ $label }}</flux:table.column>
                                @endforeach
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($previewRows as $row)
                                    <flux:table.row>
                                        @foreach($importType === 'schools' ? $this->getSchoolMappingFields() : $this->getContactMappingFields() as $key => $label)
                                            <flux:table.cell>
                                                @php
                                                    $col = $columnMapping[$key] ?? null;
                                                    $val = $col !== null && isset($row[$col]) ? $row[$col] : '';
                                                @endphp
                                                {{ is_scalar($val) ? Str::limit((string)$val, 30) : '' }}
                                            </flux:table.cell>
                                        @endforeach
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                    @if(empty($previewRows) && $totalFiltered === 0)
                        <flux:text class="text-zinc-500 mt-2">{{ __('No rows match the current mapping and filters.') }}</flux:text>
                    @endif
                    <div class="mt-6 flex gap-4">
                        <flux:button size="sm" variant="primary" wire:click="runImport" wire:confirm="{{ __('Import :count row(s)?', ['count' => $totalFiltered]) }}">
                            {{ __('Import :count row(s)', ['count' => $totalFiltered]) }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="startOver">{{ __('Cancel') }}</flux:button>
                    </div>
                @endif
            </flux:card>
        @endif
    @endif
</div>
