<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Models\School;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Schools'])]
class CampaignSchools extends Component
{
    public Campaign $campaign;

    public $official_name = '';

    public $address = '';

    public $city = '';

    public $state = '';

    public $country = 'Romania';

    public $access_token = '';

    public $latitude = '';

    public $longitude = '';

    public $contact_person = '';

    public $contact_phone = '';

    public $contact_email = '';

    public $target_kits = 0;

    public $school_id;

    public string $schoolSearch = '';

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function openNewSchoolModal(): void
    {
        $this->reset([
            'official_name', 'address', 'city', 'state', 'country', 'access_token',
            'latitude', 'longitude', 'contact_person', 'contact_phone', 'contact_email', 'target_kits',
            'school_id', 'schoolSearch',
        ]);
        $this->country = 'Romania';
        Flux::modal('school-modal')->show();
    }

    public function getSchoolSearchResultsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        $q = $this->schoolSearch;
        if (strlen($q) < 2) {
            return new \Illuminate\Database\Eloquent\Collection;
        }
        $term = '%'.trim($q).'%';

        return School::query()
            ->where(function ($query) use ($term) {
                $query->where('official_name', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('city', 'like', $term);
            })
            ->orderBy('official_name')
            ->limit(15)
            ->get();
    }

    public function selectSchoolFromSearch(string $id): void
    {
        $school = School::find($id);
        if (! $school) {
            return;
        }
        $this->school_id = $school->id;
        $this->official_name = $school->official_name;
        $this->address = $school->address;
        $this->city = $school->city ?? '';
        $this->state = $school->state ?? '';
        $this->country = $school->country ?? 'Romania';
        $this->access_token = $school->access_token;
        $this->latitude = $school->latitude ?? '';
        $this->longitude = $school->longitude ?? '';
        $this->contact_person = $school->contact_person ?? '';
        $this->contact_phone = $school->contact_phone ?? '';
        $this->contact_email = $school->contact_email ?? '';
        $this->target_kits = $school->target_kits ?? 0;
        $this->schoolSearch = '';
        Flux::toast(__('School data loaded. You can edit and save to update this school.'), 'success');
    }

    public function save(): void
    {
        $this->validate([
            'official_name' => 'required|string',
            'address' => 'required|string',
        ]);

        if (! $this->access_token) {
            $this->access_token = (string) Str::uuid();
        }

        School::updateOrCreate(
            ['id' => $this->school_id],
            [
                'official_name' => $this->official_name,
                'address' => $this->address,
                'city' => $this->city ?: null,
                'state' => $this->state ?: null,
                'country' => $this->country ?: 'Romania',
                'campaign_id' => $this->campaign->id,
                'access_token' => $this->access_token,
                'latitude' => $this->latitude ?: null,
                'longitude' => $this->longitude ?: null,
                'contact_person' => $this->contact_person ?: null,
                'contact_phone' => $this->contact_phone ?: null,
                'contact_email' => $this->contact_email ?: null,
                'target_kits' => (int) $this->target_kits,
            ]
        );

        $this->reset(['official_name', 'address', 'city', 'state', 'country', 'access_token', 'latitude', 'longitude', 'contact_person', 'contact_phone', 'contact_email', 'target_kits', 'school_id', 'schoolSearch']);
        Flux::modal('school-modal')->close();
        Flux::toast(__('School saved successfully.'), 'success');
    }

    public function edit(string $id): void
    {
        $school = School::where('campaign_id', $this->campaign->id)->findOrFail($id);
        $this->school_id = $school->id;
        $this->official_name = $school->official_name;
        $this->address = $school->address;
        $this->city = $school->city ?? '';
        $this->state = $school->state ?? '';
        $this->country = $school->country ?? 'Romania';
        $this->access_token = $school->access_token;
        $this->latitude = $school->latitude ?? '';
        $this->longitude = $school->longitude ?? '';
        $this->contact_person = $school->contact_person ?? '';
        $this->contact_phone = $school->contact_phone ?? '';
        $this->contact_email = $school->contact_email ?? '';
        $this->target_kits = $school->target_kits ?? 0;
        $this->schoolSearch = '';
        Flux::modal('school-modal')->show();
    }

    public function delete(string $id): void
    {
        $school = School::where('campaign_id', $this->campaign->id)->findOrFail($id);
        $school->delete();
        Flux::toast(__('School deleted successfully.'));
    }

    public function copyMagicLink(string $schoolId): void
    {
        $school = School::where('campaign_id', $this->campaign->id)->findOrFail($schoolId);
        $url = route('school.access', [$school, $school->access_token]);
        $this->dispatch('copy-to-clipboard', url: $url);
        Flux::toast(__('Magic link copied to clipboard.'), 'success');
    }

    public function render()
    {
        $schools = $this->campaign->schools()->latest()->get();

        return view('livewire.campaign-schools', [
            'schools' => $schools,
            'schoolSearchResults' => $this->schoolSearchResults,
        ]);
    }
}
