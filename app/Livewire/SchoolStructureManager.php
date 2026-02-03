<?php

namespace App\Livewire;

use App\Models\School;
use App\Models\Structure;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Structures'])]
class SchoolStructureManager extends Component
{
    public School $school;

    public string $name = '';

    public int $target_kits = 0;

    public ?string $structure_id = null;

    public function mount(School $school): void
    {
        $this->school = $school;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string',
            'target_kits' => 'required|integer|min:0',
        ]);

        Structure::updateOrCreate(
            ['id' => $this->structure_id],
            [
                'school_id' => $this->school->id,
                'name' => $this->name,
                'target_kits' => (int) $this->target_kits,
            ]
        );

        $this->reset(['name', 'target_kits', 'structure_id']);
        Flux::modal('structure-modal')->close();
        Flux::toast(__('Structure saved successfully.'), 'success');
    }

    public function edit(string $id): void
    {
        $structure = Structure::findOrFail($id);

        $this->structure_id = $structure->id;
        $this->name = $structure->name;
        $this->target_kits = $structure->target_kits ?? 0;

        Flux::modal('structure-modal')->show();
    }

    public function delete(string $id): void
    {
        $structure = Structure::findOrFail($id);
        $structure->delete();
        Flux::toast(__('Structure deleted successfully.'));
    }

    public function render()
    {
        return view('livewire.school-structure-manager', [
            'structures' => $this->school->structures()->latest()->get(),
        ]);
    }
}
