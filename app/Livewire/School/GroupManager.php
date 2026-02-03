<?php

namespace App\Livewire\School;

use App\Models\Child;
use App\Models\School;
use App\Models\Structure;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.school')]
class GroupManager extends Component
{
    public School $school;

    public Structure $structure;

    public string $group_name = '';

    public string $educator_name = '';

    public string $contact_phone = '';

    public int $target_kits = 0;

    public $group_id = null;

    public $active_group_id;

    public $child_id = null;

    public string $child_full_name = '';

    public string $parent_full_name = '';

    public string $structure_address = '';

    public ?float $structure_latitude = null;

    public ?float $structure_longitude = null;

    public function mount(School $school, Structure $structure): void
    {
        $this->school = $school;
        $this->structure = $structure;
    }

    public function openAddGroupModal(): void
    {
        $this->group_id = null;
        $this->reset(['group_name', 'educator_name', 'contact_phone', 'target_kits']);
        Flux::modal('add-group-modal')->show();
    }

    public function editGroup(string $id): void
    {
        $group = $this->structure->groups()->findOrFail($id);
        $this->group_id = $group->id;
        $this->group_name = $group->name;
        $this->educator_name = $group->educator_name ?? '';
        $this->contact_phone = $group->contact_phone ?? '';
        $this->target_kits = $group->target_kits ?? 0;
        Flux::modal('add-group-modal')->show();
    }

    public function createGroup(): void
    {
        $this->validate([
            'group_name' => 'required|string',
            'educator_name' => 'required|string',
            'contact_phone' => 'nullable|string',
            'target_kits' => 'required|integer|min:0',
        ]);

        $data = [
            'name' => $this->group_name,
            'educator_name' => $this->educator_name,
            'contact_phone' => $this->contact_phone ?: null,
            'target_kits' => $this->target_kits,
        ];

        if ($this->group_id) {
            $this->structure->groups()->findOrFail($this->group_id)->update($data);
            Flux::toast(__('Group updated successfully.'), 'success');
        } else {
            $this->structure->groups()->create($data);
            Flux::toast(__('Group created successfully.'), 'success');
        }

        $this->reset(['group_id', 'group_name', 'educator_name', 'contact_phone', 'target_kits']);
        Flux::modal('add-group-modal')->close();
    }

    public function deleteGroup(string $id): void
    {
        $group = $this->structure->groups()->findOrFail($id);
        $group->children()->delete();
        $group->delete();
        Flux::toast(__('Group deleted.'), 'success');
    }

    public function openAddChildModal(string $groupId): void
    {
        $this->active_group_id = $groupId;
        $this->child_id = null;
        $this->reset(['child_full_name', 'parent_full_name']);
        Flux::modal('add-child-modal')->show();
    }

    public function editChild(string $id): void
    {
        $child = Child::whereHas('group', fn ($q) => $q->where('structure_id', $this->structure->id))
            ->findOrFail($id);
        $this->child_id = $child->id;
        $this->active_group_id = $child->group_id;
        $this->child_full_name = $child->child_full_name;
        $this->parent_full_name = $child->parent_full_name;
        Flux::modal('add-child-modal')->show();
    }

    public function saveChild(bool $andAddAnother = false): void
    {
        $this->validate([
            'child_full_name' => 'required|string',
            'parent_full_name' => 'required|string',
        ]);

        $data = [
            'child_full_name' => $this->child_full_name,
            'parent_full_name' => $this->parent_full_name,
        ];

        $wasEdit = (bool) $this->child_id;

        if ($this->child_id) {
            Child::whereHas('group', fn ($q) => $q->where('structure_id', $this->structure->id))
                ->findOrFail($this->child_id)
                ->update($data);
            Flux::toast(__('Child updated successfully.'), 'success');
        } else {
            Child::create([
                ...$data,
                'gdpr_status' => true,
                'group_id' => $this->active_group_id,
            ]);
            Flux::toast(__('Child added successfully.'), 'success');
        }

        $this->reset(['child_id', 'child_full_name', 'parent_full_name']);

        if ($andAddAnother && ! $wasEdit) {
            Flux::toast(__('Child added. Add another?'), 'success');
        } elseif (! $andAddAnother || $wasEdit) {
            $this->reset(['active_group_id']);
            Flux::modal('add-child-modal')->close();
        }
    }

    public function deleteChild(string $id): void
    {
        Child::whereHas('group', fn ($q) => $q->where('structure_id', $this->structure->id))
            ->findOrFail($id)
            ->delete();
        Flux::toast(__('Child removed.'), 'success');
    }

    public function openStructureLocationModal(): void
    {
        $this->structure_address = $this->structure->address ?? '';
        $this->structure_latitude = $this->structure->latitude;
        $this->structure_longitude = $this->structure->longitude;
        Flux::modal('structure-location-modal')->show();
    }

    public function saveStructureLocation(): void
    {
        $this->validate([
            'structure_address' => 'required|string',
        ]);

        $this->structure->update([
            'address' => $this->structure_address,
            'latitude' => $this->structure_latitude,
            'longitude' => $this->structure_longitude,
        ]);

        Flux::modal('structure-location-modal')->close();
        Flux::toast(__('Location saved.'));
    }

    public function render()
    {
        return view('livewire.school.group-manager', [
            'groups' => $this->structure->groups()
                ->with('children')
                ->latest()
                ->get(),
        ]);
    }
}
