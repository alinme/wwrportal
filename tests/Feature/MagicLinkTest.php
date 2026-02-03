<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;

test('magic link logs in school user', function () {
    $school = School::factory()->create([
        'access_token' => (string) Str::uuid(),
        'official_name' => 'Magic School',
    ]);

    $response = $this->get(route('school.access', [
        'school' => $school->id,
        'token' => $school->access_token
    ]));

    $response->assertRedirect(route('school.dashboard', $school));
    
    $this->assertAuthenticated();
    $this->assertEquals('Magic School', auth()->user()->name);
});

test('magic link fails with invalid token', function () {
    $school = School::factory()->create();

    $response = $this->get(route('school.access', [
        'school' => $school->id,
        'token' => 'invalid-token'
    ]));

    $response->assertStatus(403);
    $this->assertGuest();
});
