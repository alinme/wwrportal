<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated admin users can visit the dashboard', function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'admin']);
    $user->assignRole('admin');
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('school manager is redirected to their portal when visiting dashboard', function () {
    $school = \App\Models\School::factory()->create();
    Role::create(['name' => 'school_manager', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'school_manager', 'school_id' => $school->id]);
    $user->assignRole('school_manager');
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('school.dashboard', $school));
});
