<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Models\School;
use App\Models\Structure;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Structures'])]
class SchoolStructureManager extends Component
{
    public School $school;

    /** When set, we were opened from campaign → schools (back goes to campaign schools). */
    public ?Campaign $campaign = null;

    public string $name = '';

    public int $target_kits = 0;

    public bool $same_location_as_school = false;

    public ?string $structure_id = null;

    public int $kits_returned = 0;

    public int $kits_received_from_return = 0;

    public function mount(School $school, ?Campaign $campaign = null): void
    {
        $this->school = $school;
        $this->campaign = $campaign;
        $this->kits_returned = (int) ($school->kits_returned ?? 0);
        $this->kits_received_from_return = (int) ($school->kits_received_from_return ?? 0);
    }

    public function saveReturPrimire(): void
    {
        $this->validate([
            'kits_returned' => 'required|integer|min:0',
            'kits_received_from_return' => 'required|integer|min:0',
        ]);
        $this->school->update([
            'kits_returned' => $this->kits_returned,
            'kits_received_from_return' => $this->kits_received_from_return,
        ]);
        Flux::toast(__('Saved.'), 'success');
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string',
            'target_kits' => 'required|integer|min:0',
        ]);

        $data = [
            'school_id' => $this->school->id,
            'name' => $this->name,
            'target_kits' => (int) $this->target_kits,
            'same_location_as_school' => $this->same_location_as_school,
        ];

        if ($this->same_location_as_school) {
            $data['address'] = $this->school->address ?? null;
            $data['latitude'] = $this->school->latitude;
            $data['longitude'] = $this->school->longitude;
        } elseif (empty($this->structure_id)) {
            $data['address'] = $this->school->address ?? '';
            $data['latitude'] = $this->school->latitude;
            $data['longitude'] = $this->school->longitude;
        }

        Structure::updateOrCreate(
            ['id' => $this->structure_id],
            $data
        );

        $this->reset(['name', 'target_kits', 'same_location_as_school', 'structure_id']);
        Flux::modal('structure-modal')->close();
        Flux::toast(__('Structure saved successfully.'), 'success');
    }

    public function edit(string $id): void
    {
        $structure = Structure::findOrFail($id);

        $this->structure_id = $structure->id;
        $this->name = $structure->name;
        $this->target_kits = $structure->target_kits ?? 0;
        $this->same_location_as_school = $structure->same_location_as_school ?? false;

        Flux::modal('structure-modal')->show();
    }

    public function delete(string $id): void
    {
        $structure = Structure::findOrFail($id);
        $structure->delete();
        Flux::toast(__('Structure deleted successfully.'));
    }

    public function copyPortalLink(): void
    {
        $url = url()->route('school.access', [$this->school, $this->school->access_token]);
        $this->dispatch('copy-to-clipboard', url: $url);
        Flux::toast(__('Portal link copied to clipboard.'), 'success');
    }

    public function render()
    {
        $structures = $this->school->structures()->latest()->get();
        $groupsCount = $this->school->groups()->count();
        $childrenCount = $this->school->groups()->withCount('children')->get()->sum('children_count');
        $totalTargetKits = $structures->sum('target_kits');

        return view('livewire.school-structure-manager', [
            'structures' => $structures,
            'structures_count' => $structures->count(),
            'groups_count' => $groupsCount,
            'children_count' => $childrenCount,
            'total_target_kits' => (int) $totalTargetKits,
            'campaign' => $this->campaign,
        ]);
    }
}
