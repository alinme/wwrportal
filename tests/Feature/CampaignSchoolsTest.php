<?php

use App\Livewire\CampaignSchools;
use App\Models\Campaign;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;

test('campaign schools page requires auth', function () {
    $campaign = Campaign::factory()->create();

    $this->get(route('campaigns.schools', $campaign))
        ->assertRedirect(route('login'));
});

test('open new school modal resets form and search', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    Livewire::actingAs($user)
        ->test(CampaignSchools::class, ['campaign' => $campaign])
        ->set('official_name', 'Some')
        ->set('schoolSearch', 'query')
        ->call('openNewSchoolModal')
        ->assertSet('official_name', '')
        ->assertSet('schoolSearch', '')
        ->assertSet('school_id', null);
});

test('school search returns results when query has at least two characters', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    School::factory()->create(['official_name' => 'Scoala Speciala Alpha', 'campaign_id' => $campaign->id]);

    $results = Livewire::actingAs($user)
        ->test(CampaignSchools::class, ['campaign' => $campaign])
        ->set('schoolSearch', 'Al')
        ->viewData('schoolSearchResults');

    expect($results)->toHaveCount(1);
    expect($results->first()->official_name)->toBe('Scoala Speciala Alpha');
});

test('school search returns empty when query is too short', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    School::factory()->create(['official_name' => 'Scoala']);

    $results = Livewire::actingAs($user)
        ->test(CampaignSchools::class, ['campaign' => $campaign])
        ->set('schoolSearch', 'S')
        ->viewData('schoolSearchResults');

    expect($results)->toHaveCount(0);
});

test('select school from search populates form and sets school_id', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $school = School::factory()->create([
        'official_name' => 'Liceul Test',
        'address' => 'Str. Example 1',
        'city' => 'București',
        'campaign_id' => $campaign->id,
    ]);

    Livewire::actingAs($user)
        ->test(CampaignSchools::class, ['campaign' => $campaign])
        ->call('selectSchoolFromSearch', (string) $school->id)
        ->assertSet('school_id', $school->id)
        ->assertSet('official_name', 'Liceul Test')
        ->assertSet('address', 'Str. Example 1')
        ->assertSet('city', 'București')
        ->assertSet('schoolSearch', '');
});

test('saving after selecting school updates that school', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $school = School::factory()->create([
        'official_name' => 'Original Name',
        'address' => 'Original Address',
        'campaign_id' => $campaign->id,
    ]);

    Livewire::actingAs($user)
        ->test(CampaignSchools::class, ['campaign' => $campaign])
        ->call('selectSchoolFromSearch', (string) $school->id)
        ->set('official_name', 'Updated Name')
        ->set('address', 'Updated Address')
        ->call('save')
        ->assertHasNoErrors();

    $school->refresh();
    expect($school->official_name)->toBe('Updated Name');
    expect($school->address)->toBe('Updated Address');
});
