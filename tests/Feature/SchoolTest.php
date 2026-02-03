<?php

use App\Livewire\Schools;
use App\Models\Campaign;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;

test('an admin can get schools component', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schools'))
        ->assertSeeLivewire(Schools::class)
        ->assertOk();
});

test('can create a school', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(Schools::class)
        ->set('official_name', 'Test School')
        ->set('address', 'Test Address')
        ->set('campaign_id', $campaign->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(School::count())->toBe(1);
    expect(School::first()->official_name)->toBe('Test School');
    expect(School::first()->access_token)->not->toBeNull(); // Check UUID token generation
});

test('can manage schools', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();

    Livewire::actingAs($user)
        ->test(Schools::class)
        ->call('edit', (string) $school->id)
        ->assertSet('official_name', $school->official_name)
        ->set('official_name', 'Updated School')
        ->call('save');

    expect($school->fresh()->official_name)->toBe('Updated School');
});
