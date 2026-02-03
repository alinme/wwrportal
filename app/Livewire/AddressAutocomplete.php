<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class AddressAutocomplete extends Component
{
    public string $address = '';

    public string $label = '';

    public string $placeholder = '';

    #[On('set-address')]
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function render()
    {
        return view('livewire.address-autocomplete', [
            'apiKey' => config('services.google.places_api_key', ''),
        ]);
    }
}
