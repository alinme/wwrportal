<?php

namespace App\Livewire;

use App\Models\School;
use Livewire\Component;

class SchoolsMap extends Component
{
    public function render()
    {
        $schools = School::with(['campaign', 'structures.groups.children'])
            ->get()
            ->map(function (School $school) {
                $childrenCount = $school->structures->sum(fn ($s) => $s->groups->sum(fn ($g) => $g->children->count()));
                $targetKits = (int) ($school->target_kits ?? 0);
                $isReady = $targetKits > 0 ? $childrenCount >= $targetKits : $childrenCount > 0;

                return [
                    'id' => $school->id,
                    'name' => $school->official_name,
                    'address' => $school->address,
                    'latitude' => $school->latitude ? (float) $school->latitude : null,
                    'longitude' => $school->longitude ? (float) $school->longitude : null,
                    'status' => $isReady ? 'ready' : 'empty',
                ];
            });

        return view('livewire.schools-map', [
            'schools' => $schools,
        ]);
    }
}
