<?php

use App\Models\Campaign;
use App\Models\School;
use App\Models\User;

test('school can download contract', function () {
    $school = School::factory()->create();
    $campaign = Campaign::factory()->create();
    
    // Assign campaign indirectly or if we had logic for "current"
    // The controller uses $school->campaign.
    // We need to ensure the factory sets it or we set it manually.
    // Checking School factory: it has campaign_id?
    // Let's check School model/factory. Assuming BelongsTo.
    
    $school->campaign_id = $campaign->id;
    $school->save();

    // Login as school manager (or how we access portal)
    // The Docs route is protected by 'auth', 'verified', 'portal/{school}'
    
    // Simulate login via magic link first or just acting as a user
    $user = User::factory()->create(['email' => "school_{$school->id}@portal.local"]);
    
    $this->actingAs($user)
        ->get(route('school.docs.contract', $school))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('school can download annex', function () {
    $school = School::factory()->create();
    $campaign = Campaign::factory()->create();
    $school->campaign_id = $campaign->id;
    $school->save();
    
    $user = User::factory()->create(['email' => "school_{$school->id}@portal.local"]);

    $this->actingAs($user)
        ->get(route('school.docs.annex', $school))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf'); // application/pdf
});
