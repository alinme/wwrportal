<?php

namespace App\Livewire\School;

use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.school')]
class SchoolDashboard extends Component
{
    public School $school;

    public function mount(School $school)
    {
        $this->school = $school;
    }

    public function render()
    {
        return view('livewire.school.school-dashboard', [
            'structures' => $this->school->structures()->with('groups.children')->get(),
        ]);
    }
}
