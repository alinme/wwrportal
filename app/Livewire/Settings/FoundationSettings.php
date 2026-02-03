<?php

namespace App\Livewire\Settings;

use App\Models\Foundation;
use Flux\Flux;
use Livewire\Component;

class FoundationSettings extends Component
{
    public $name = 'World Vision Romania';
    public $details = '';
    public $logo_path = '';

    public function mount()
    {
        $foundation = Foundation::first();
        if ($foundation) {
            $this->name = $foundation->name;
            $this->details = $foundation->details;
            $this->logo_path = $foundation->logo_path;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string',
            'details' => 'nullable|string',
            'logo_path' => 'nullable|string',
        ]);

        $foundation = Foundation::first();

        if ($foundation) {
            $foundation->update([
                'name' => $this->name,
                'details' => $this->details,
                'logo_path' => $this->logo_path,
            ]);
        } else {
            Foundation::create([
                'name' => $this->name,
                'details' => $this->details,
                'logo_path' => $this->logo_path,
            ]);
        }

        Flux::toast('Foundation details saved.');
    }

    public function render()
    {
        return view('livewire.settings.foundation-settings');
    }
}
