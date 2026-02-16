<?php

use App\Livewire\Uploads;
use App\Models\Campaign;
use App\Models\School;
use App\Models\Structure;
use App\Models\User;
use Livewire\Livewire;

test('school import auto-creates one structure with school name and address', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $component = Livewire::actingAs($user)->test(Uploads::class);
    $component->set('importType', 'schools');
    $component->set('columnMapping', ['official_name' => 0, 'address' => 1]);
    $component->set('defaultCampaignId', $campaign->id);

    $row = [
        0 => 'Liceul Test Import',
        1 => 'Str. Example 123',
    ];

    $method = new ReflectionMethod(Uploads::class, 'importSchools');
    $method->setAccessible(true);
    $method->invoke($component->instance(), [$row]);

    expect(School::count())->toBe(1);
    expect(Structure::count())->toBe(1);

    $school = School::first();
    $structure = Structure::first();

    expect($school->official_name)->toBe('Liceul Test Import');
    expect($school->address)->toBe('Str. Example 123');
    expect($structure->school_id)->toBe($school->id);
    expect($structure->name)->toBe('Liceul Test Import');
    expect($structure->address)->toBe('Str. Example 123');
});

test('school import adds self structure then structures from Excel column', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $component = Livewire::actingAs($user)->test(Uploads::class);
    $component->set('importType', 'schools');
    $component->set('columnMapping', [
        'official_name' => 0,
        'address' => 1,
        'structures_text' => 2,
    ]);
    $component->set('defaultCampaignId', $campaign->id);

    $row = [
        0 => 'Scoala Nr. 1',
        1 => 'Str. Principala 1',
        2 => 'Gradinita A / Gradinita B',
    ];

    $method = new ReflectionMethod(Uploads::class, 'importSchools');
    $method->setAccessible(true);
    $method->invoke($component->instance(), [$row]);

    $school = School::first();
    expect($school)->not->toBeNull();

    $structures = Structure::where('school_id', $school->id)->get();
    expect($structures)->toHaveCount(3); // self + Gradinita A + Gradinita B

    $names = $structures->pluck('name')->sort()->values()->all();
    expect($names)->toEqual(['Gradinita A', 'Gradinita B', 'Scoala Nr. 1']);
});
