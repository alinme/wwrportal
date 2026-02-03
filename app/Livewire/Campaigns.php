<?php

namespace App\Livewire;

use App\Models\Campaign;
use Flux\Flux;
use Livewire\Component;

class Campaigns extends Component
{
    public $name = '';
    public $facilitator_name = 'Moraru Alin Eduard';
    public $month_year_suffix = '';
    public $target_kits = 600;
    public $is_active = true;

    public $campaign_id;
    public $confirmingDeletion = false;

    public function mount()
    {
        $this->month_year_suffix = '.' . date('m.Y');
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string',
            'facilitator_name' => 'required|string',
            'month_year_suffix' => 'required|string',
            'target_kits' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        Campaign::updateOrCreate(
            ['id' => $this->campaign_id],
            [
                'name' => $this->name,
                'facilitator_name' => $this->facilitator_name,
                'month_year_suffix' => $this->month_year_suffix,
                'target_kits' => $this->target_kits,
                'is_active' => $this->is_active,
            ]
        );

        $this->reset(['name', 'campaign_id']);
        $this->is_active = true; // Default back to true

        Flux::modal('campaign-modal')->close();
        Flux::toast('Campaign saved successfully.', 'success');
    }

    public function edit(Campaign $campaign)
    {
        $this->campaign_id = $campaign->id;
        $this->name = $campaign->name;
        $this->facilitator_name = $campaign->facilitator_name;
        $this->month_year_suffix = $campaign->month_year_suffix;
        $this->target_kits = $campaign->target_kits;
        $this->is_active = $campaign->is_active;

        Flux::modal('campaign-modal')->show();
    }

    public function delete(Campaign $campaign)
    {
        $campaign->delete();
        Flux::toast('Campaign deleted successfully.');
    }

    public function render()
    {
        return view('livewire.campaigns', [
            'campaigns' => Campaign::latest()->get()
        ]);
    }
}
