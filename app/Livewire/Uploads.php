<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\School;
use App\Models\Structure;
use App\Services\ExcelReaderService;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['title' => 'Uploads & Import'])]
class Uploads extends Component
{
    use WithFileUploads;

    public string $activeTab = 'schools'; // 'schools' | 'contacts' | 'manual-contact'

    // File & sheet
    public $file = null;

    public ?string $storedPath = null;

    public int $step = 1;

    public int $sheetIndex = 0;

    public int $headerRow = 1;

    public string $importType = 'schools'; // 'schools' | 'contacts'

    /** @var array<string, int> our field key => Excel column index (only ints for Livewire serialization) */
    public array $columnMapping = [];

    /** @var array<string, string> our field key => default value (only strings for Livewire) */
    public array $defaults = [];

    public ?int $defaultCampaignId = null;

    /** @var list<array{col: int, op: string, value: string}> */
    public array $filters = [];

    public array $previewRows = [];

    public int $totalFiltered = 0;

    /**
     * Normalize after receiving from frontend (e.g. Flux select may send unexpected types).
     */
    public function hydrate(): void
    {
        $this->normalizeColumnMapping();
        $this->normalizeDefaults();
        $this->normalizeFilters();
        if ($this->defaultCampaignId === '' || $this->defaultCampaignId === null) {
            $this->defaultCampaignId = null;
        } elseif (! is_int($this->defaultCampaignId) && ! is_numeric($this->defaultCampaignId)) {
            $this->defaultCampaignId = null;
        } else {
            $this->defaultCampaignId = (int) $this->defaultCampaignId;
        }
    }

    /**
     * Normalize before sending to frontend so Livewire only serializes scalars.
     */
    public function dehydrate(): void
    {
        $this->normalizeColumnMapping();
        $this->normalizeDefaults();
        $this->normalizeFilters();
        $this->normalizePreviewRows();
        if (! is_int($this->defaultCampaignId) && ! is_null($this->defaultCampaignId)) {
            $this->defaultCampaignId = is_numeric($this->defaultCampaignId) ? (int) $this->defaultCampaignId : null;
        }
    }

    protected function normalizeColumnMapping(): void
    {
        $allowedKeys = array_keys($this->importType === 'schools' ? $this->getSchoolMappingFields() : $this->getContactMappingFields());
        $clean = [];
        $mapping = is_array($this->columnMapping) ? $this->columnMapping : (array) $this->columnMapping;
        foreach ($mapping as $k => $v) {
            $k = (string) $k;
            if (! in_array($k, $allowedKeys, true)) {
                continue;
            }
            if (is_object($v)) {
                continue;
            }
            if ($v !== '' && $v !== null && is_numeric($v)) {
                $clean[$k] = (int) $v;
            }
        }
        $this->columnMapping = $clean;
    }

    protected function normalizeDefaults(): void
    {
        $clean = [];
        $defaults = is_array($this->defaults) ? $this->defaults : (array) $this->defaults;
        foreach ($defaults as $k => $v) {
            if (is_object($v)) {
                continue;
            }
            if (is_scalar($v) && $v !== '' && $v !== null) {
                $clean[(string) $k] = (string) $v;
            }
        }
        $this->defaults = $clean;
    }

    protected function normalizeFilters(): void
    {
        $this->filters = array_values(array_map(function ($f) {
            $f = is_array($f) ? $f : [];

            return [
                'col' => (int) ($f['col'] ?? 0),
                'op' => (string) ($f['op'] ?? 'not_empty'),
                'value' => (string) ($f['value'] ?? ''),
            ];
        }, $this->filters));
    }

    /**
     * Ensure previewRows only contains scalars (Excel can return DateTime etc.).
     */
    protected function normalizePreviewRows(): void
    {
        $clean = [];
        foreach ($this->previewRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cleanRow = [];
            foreach ($row as $k => $v) {
                $cleanRow[(int) $k] = $this->toScalar($v);
            }
            $clean[] = $cleanRow;
        }
        $this->previewRows = $clean;
    }

    private function toScalar(mixed $v): string|int|float|bool|null
    {
        if ($v === null || is_scalar($v)) {
            return $v;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d H:i:s');
        }
        if (is_object($v) && method_exists($v, '__toString')) {
            return (string) $v;
        }

        return (string) $v;
    }

    // Manual contact form
    public string $contact_name = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $contact_organization = '';

    public string $contact_notes = '';

    public function getSchoolMappingFields(): array
    {
        return [
            'official_name' => __('School name'),
            'address' => __('Address'),
            'city' => __('City'),
            'state' => __('County'),
            'country' => __('Country'),
            'latitude' => __('Latitude'),
            'longitude' => __('Longitude'),
            'contact_person' => __('Contact person'),
            'contact_phone' => __('Contact phone'),
            'contact_email' => __('Contact email'),
            'target_kits' => __('Target kits'),
            'structures_text' => __('Structures (separated by /)'),
            'campaign' => __('Campaign (name or leave empty)'),
        ];
    }

    public function getContactMappingFields(): array
    {
        return [
            'name' => __('Name'),
            'email' => __('Email'),
            'phone' => __('Phone'),
            'organization' => __('Organization'),
            'notes' => __('Notes'),
        ];
    }

    public function updatedActiveTab(string $value): void
    {
        if (in_array($value, ['schools', 'contacts'], true)) {
            $this->importType = $value;
        }
    }

    public static function filterOperators(): array
    {
        return [
            'not_empty' => __('is not empty'),
            'empty' => __('is empty'),
            'equals' => __('equals'),
            'contains' => __('contains'),
            'greater_than' => __('greater than'),
            'less_than' => __('less than'),
            'gte' => __('greater or equal'),
            'lte' => __('less or equal'),
        ];
    }

    public function updatedFile(): void
    {
        $this->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        $this->storedPath = $this->file->getRealPath();
        $this->step = 2;
        $this->columnMapping = [];
        $this->filters = [];
        $this->headerRow = 1;
        $this->sheetIndex = 0;
    }

    public function setSheet(int $index): void
    {
        $this->sheetIndex = $index;
        $this->step = 3;
        // Sheet name is often the Județ – pre-fill default for schools import
        if ($this->importType === 'schools' && $this->storedPath && file_exists($this->storedPath)) {
            $reader = (new ExcelReaderService)->load($this->storedPath);
            $names = $reader->getSheetNames();
            $sheetName = $names[$index] ?? '';
            if ($sheetName !== '') {
                $this->defaults['state'] = $sheetName;
            }
            $this->defaults['country'] = 'Romania';
        }
    }

    public function setHeaderRow(int $row): void
    {
        $this->headerRow = $row;
        $this->step = 4;
        $this->columnMapping = [];
        // Re-apply sheet name as Județ default if not already set (e.g. when coming back to step 4)
        if ($this->importType === 'schools' && $this->storedPath && file_exists($this->storedPath)) {
            $reader = (new ExcelReaderService)->load($this->storedPath);
            $names = $reader->getSheetNames();
            $sheetName = $names[$this->sheetIndex] ?? '';
            if ($sheetName !== '' && empty($this->defaults['state'])) {
                $this->defaults['state'] = $sheetName;
            }
            $this->defaults['country'] = 'Romania';
        }
    }

    public function setMapping(string $ourFieldKey, $excelColIndex): void
    {
        $allowed = $this->importType === 'schools'
            ? array_keys($this->getSchoolMappingFields())
            : array_keys($this->getContactMappingFields());
        if (! in_array($ourFieldKey, $allowed, true)) {
            return;
        }
        if ($excelColIndex === '' || $excelColIndex === null) {
            unset($this->columnMapping[$ourFieldKey]);
        } else {
            $this->columnMapping[$ourFieldKey] = (int) $excelColIndex;
        }
    }

    public function setDefault(string $key, $value): void
    {
        if ($value === '' || $value === null) {
            unset($this->defaults[$key]);
        } else {
            $this->defaults[$key] = $value;
        }
    }

    public function addFilter(): void
    {
        $this->filters[] = ['col' => 0, 'op' => 'not_empty', 'value' => ''];
    }

    public function removeFilter(int $index): void
    {
        array_splice($this->filters, $index, 1);
    }

    public function goToPreview(): void
    {
        $this->step = 5;
        $this->buildPreview();
    }

    public function buildPreview(): void
    {
        if (! $this->storedPath || ! file_exists($this->storedPath)) {
            $this->previewRows = [];
            $this->totalFiltered = 0;

            return;
        }
        $reader = (new ExcelReaderService)->load($this->storedPath);
        $allRows = $reader->getRows($this->sheetIndex, $this->headerRow, 500);
        $filtered = $this->applyFilters($allRows);
        $this->totalFiltered = count($filtered);
        $this->previewRows = array_slice($filtered, 0, 50);
    }

    protected function applyFilters(array $rows): array
    {
        if (empty($this->filters)) {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) {
            foreach ($this->filters as $f) {
                $col = $f['col'];
                $op = $f['op'];
                $val = isset($row[$col]) ? trim((string) $row[$col]) : '';
                $compare = $f['value'] ?? '';
                if (! $this->rowPassesFilter($val, $op, $compare)) {
                    return false;
                }
            }

            return true;
        }));
    }

    protected function rowPassesFilter(string $cellVal, string $op, string $compare): bool
    {
        switch ($op) {
            case 'not_empty':
                return $cellVal !== '';
            case 'empty':
                return $cellVal === '';
            case 'equals':
                return $cellVal === $compare;
            case 'contains':
                return str_contains($cellVal, $compare);
            case 'greater_than':
                return is_numeric($cellVal) && is_numeric($compare) && (float) $cellVal > (float) $compare;
            case 'less_than':
                return is_numeric($cellVal) && is_numeric($compare) && (float) $cellVal < (float) $compare;
            case 'gte':
                return is_numeric($cellVal) && is_numeric($compare) && (float) $cellVal >= (float) $compare;
            case 'lte':
                return is_numeric($cellVal) && is_numeric($compare) && (float) $cellVal <= (float) $compare;
            default:
                return true;
        }
    }

    public function runImport(): void
    {
        if (! $this->storedPath || ! file_exists($this->storedPath)) {
            Flux::toast(__('File no longer available. Please upload again.'), 'danger');

            return;
        }
        $reader = (new ExcelReaderService)->load($this->storedPath);
        $allRows = $reader->getRows($this->sheetIndex, $this->headerRow, 10000);
        $filtered = $this->applyFilters($allRows);

        if ($this->importType === 'schools') {
            $count = $this->importSchools($filtered);
            Flux::toast(__(':count school(s) imported.', ['count' => $count]), 'success');
        } else {
            $count = $this->importContacts($filtered);
            Flux::toast(__(':count contact(s) imported.', ['count' => $count]), 'success');
        }

        $this->step = 1;
        $this->file = null;
        $this->storedPath = null;
        $this->reset(['columnMapping', 'filters', 'previewRows', 'defaults', 'defaultCampaignId']);
    }

    protected function getMappedValue(array $row, string $key): mixed
    {
        $col = $this->columnMapping[$key] ?? null;
        if ($col !== null && array_key_exists($col, $row)) {
            $v = $row[$col];
            if ($v !== null && $v !== '') {
                $keepNumeric = in_array($key, ['latitude', 'longitude', 'target_kits'], true);

                return $keepNumeric && is_numeric($v) ? (float) $v : trim((string) $v);
            }
        }

        return $this->defaults[$key] ?? null;
    }

    /**
     * @return int number of schools imported
     */
    protected function importSchools(array $rows): int
    {
        $campaignsByName = Campaign::all()->keyBy('name');
        $imported = 0;
        foreach ($rows as $row) {
            $officialName = $this->getMappedValue($row, 'official_name');
            if (! $officialName) {
                continue;
            }
            $campaignId = $this->defaultCampaignId;
            $campaignVal = $this->getMappedValue($row, 'campaign');
            if ($campaignVal && isset($campaignsByName[$campaignVal])) {
                $campaignId = $campaignsByName[$campaignVal]->id;
            }

            $school = School::create([
                'official_name' => $officialName,
                'address' => $this->getMappedValue($row, 'address') ?? '',
                'city' => $this->getMappedValue($row, 'city'),
                'state' => $this->getMappedValue($row, 'state'),
                'country' => $this->getMappedValue($row, 'country') ?? 'Romania', // always Romania for this import
                'campaign_id' => $campaignId,
                'access_token' => (string) Str::uuid(),
                'latitude' => $this->getMappedValue($row, 'latitude'),
                'longitude' => $this->getMappedValue($row, 'longitude'),
                'contact_person' => $this->getMappedValue($row, 'contact_person'),
                'contact_phone' => $this->getMappedValue($row, 'contact_phone'),
                'contact_email' => $this->getMappedValue($row, 'contact_email'),
                'target_kits' => (int) ($this->getMappedValue($row, 'target_kits') ?? 0),
            ]);

            // Auto-add one structure representing the school itself (contract partner); kits are distributed to kindergartens under it.
            Structure::create([
                'school_id' => $school->id,
                'name' => $school->official_name,
                'address' => $school->address,
                'latitude' => $school->latitude,
                'longitude' => $school->longitude,
                'target_kits' => 0,
                'same_location_as_school' => true,
            ]);

            // Then add any additional structures from Excel (e.g. kindergartens).
            $structuresText = $this->getMappedValue($row, 'structures_text');
            if ($structuresText) {
                $names = array_map('trim', explode('/', (string) $structuresText));
                foreach ($names as $name) {
                    if ($name !== '') {
                        Structure::create([
                            'school_id' => $school->id,
                            'name' => $name,
                            'address' => $school->address,
                            'latitude' => $school->latitude,
                            'longitude' => $school->longitude,
                            'target_kits' => 0,
                            'same_location_as_school' => true,
                        ]);
                    }
                }
            }
            $imported++;
        }

        return $imported;
    }

    /**
     * @return int number of contacts imported
     */
    protected function importContacts(array $rows): int
    {
        $imported = 0;
        foreach ($rows as $row) {
            $name = $this->getMappedValue($row, 'name');
            $email = $this->getMappedValue($row, 'email');
            $phone = $this->getMappedValue($row, 'phone');
            if (! $name && ! $email && ! $phone) {
                continue;
            }
            Contact::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'organization' => $this->getMappedValue($row, 'organization'),
                'notes' => $this->getMappedValue($row, 'notes'),
            ]);
            $imported++;
        }

        return $imported;
    }

    public function saveManualContact(): void
    {
        $this->validate([
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:255',
            'contact_organization' => 'nullable|string|max:255',
            'contact_notes' => 'nullable|string',
        ]);
        if (! $this->contact_name && ! $this->contact_email && ! $this->contact_phone) {
            Flux::toast(__('Provide at least name, email or phone.'), 'danger');

            return;
        }
        Contact::create([
            'name' => $this->contact_name ?: null,
            'email' => $this->contact_email ?: null,
            'phone' => $this->contact_phone ?: null,
            'organization' => $this->contact_organization ?: null,
            'notes' => $this->contact_notes ?: null,
        ]);
        $this->reset(['contact_name', 'contact_email', 'contact_phone', 'contact_organization', 'contact_notes']);
        Flux::toast(__('Contact saved.'), 'success');
    }

    public function startOver(): void
    {
        $this->step = 1;
        $this->file = null;
        $this->storedPath = null;
        $this->columnMapping = [];
        $this->filters = [];
        $this->previewRows = [];
        $this->totalFiltered = 0;
    }

    public function render()
    {
        $reader = null;
        $sheetNames = [];
        $headerRowSample = [];
        $totalRows = 0;
        $excelColumns = [];

        if ($this->storedPath && file_exists($this->storedPath)) {
            $reader = new ExcelReaderService;
            $reader->load($this->storedPath);
            $sheetNames = $reader->getSheetNames();
            $totalRows = $reader->getRowCount($this->sheetIndex);
            $headerRowSample = $reader->getRow($this->sheetIndex, $this->headerRow);
            $excelColumns = [];
            foreach ($headerRowSample as $idx => $val) {
                $excelColumns[$idx] = ExcelReaderService::columnLetter($idx).(trim((string) $val) !== '' ? ' – '.Str::limit((string) $val, 20) : '');
            }
        }

        return view('livewire.uploads', [
            'sheetNames' => $sheetNames,
            'headerRowSample' => $headerRowSample,
            'totalRows' => $totalRows,
            'excelColumns' => $excelColumns,
            'campaigns' => Campaign::orderBy('name')->get(),
            'contacts_count' => Contact::count(),
        ]);
    }
}
