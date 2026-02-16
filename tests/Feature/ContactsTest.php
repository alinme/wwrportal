<?php

use App\Livewire\Contacts;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('guest cannot access contacts page', function () {
    $this->get(route('contacts'))
        ->assertRedirect(route('login'));
});

test('authenticated admin can access contacts page', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('contacts'))
        ->assertSeeLivewire(Contacts::class)
        ->assertOk();
});

test('search filters contacts by name email phone organization and notes', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    Contact::factory()->create(['name' => 'John Doe', 'notes' => 'Call on Monday']);
    Contact::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $component = Livewire::actingAs($user)
        ->test(Contacts::class)
        ->set('search', 'Monday');

    $contacts = $component->viewData('contacts');
    expect($contacts)->toHaveCount(1);
    expect($contacts->first()->name)->toBe('John Doe');
});

test('can save contact with notes', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(Contacts::class)
        ->set('edit_name', 'Test Person')
        ->set('edit_phone', '+40 123 456')
        ->set('edit_notes', 'Prefers email. Met at conference.')
        ->call('save');

    $contact = Contact::first();
    expect($contact)->not->toBeNull();
    expect($contact->name)->toBe('Test Person');
    expect($contact->notes)->toBe('Prefers email. Met at conference.');
});

test('can update contact notes', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $contact = Contact::factory()->create(['name' => 'Existing', 'notes' => 'Old note']);

    Livewire::actingAs($user)
        ->test(Contacts::class)
        ->call('edit', (string) $contact->id)
        ->set('edit_notes', 'Updated: call next week')
        ->call('save');

    expect($contact->fresh()->notes)->toBe('Updated: call next week');
});

test('sync from schools creates contacts and skips duplicates', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $campaign = Campaign::factory()->create();
    School::factory()->create([
        'campaign_id' => $campaign->id,
        'official_name' => 'Scoala Test',
        'contact_person' => 'Director Maria',
        'contact_phone' => '0268 111 222',
        'contact_email' => 'director@school.test',
    ]);

    Livewire::actingAs($user)->test(Contacts::class)->call('syncFromSchools');

    expect(Contact::count())->toBe(1);
    $contact = Contact::first();
    expect($contact->name)->toBe('Director Maria');
    expect($contact->email)->toBe('director@school.test');
    expect($contact->phone)->toBe('0268 111 222');
    expect($contact->organization)->toBe('Scoala Test');

    Livewire::actingAs($user)->test(Contacts::class)->call('syncFromSchools');
    expect(Contact::count())->toBe(1);
});
