<?php

use App\Livewire\Campaigns;
use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

test('a guest cannot access campaigns page', function () {
    $this->get(route('campaigns'))
        ->assertRedirect(route('login'));
});

test('a user cannot access campaigns page without verification', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->get(route('campaigns'))
        ->assertRedirect(route('verification.notice'));
});

test('an admin can get campaigns component', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('campaigns'))
        ->assertSeeLivewire(Campaigns::class)
        ->assertOk();
});

test('can create a campaign', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Campaigns::class)
        ->set('name', 'Test Campaign')
        ->set('facilitator_name', 'Test Facilitator')
        ->set('month_year_suffix', '.03.2026')
        ->set('target_kits', 100)
        ->call('save')
        ->assertHasNoErrors();

    expect(Campaign::count())->toBe(1);
    expect(Campaign::first()->name)->toBe('Test Campaign');
});

test('required fields are validated', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Campaigns::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
