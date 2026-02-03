<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Child;
use App\Models\Group;
use App\Models\School;
use App\Models\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_can_have_schools()
    {
        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'month_year_suffix' => '.10.2023',
            'target_kits' => 100,
        ]);

        $school = School::create([
            'campaign_id' => $campaign->id,
            'official_name' => 'Test School',
            'address' => 'Test Address',
            'access_token' => 'unique_token',
        ]);

        $this->assertTrue($campaign->schools->contains($school));
        $this->assertTrue(strlen($school->id) === 36); // UUID check
    }

    public function test_school_can_have_structures()
    {
        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'month_year_suffix' => '.10.2023',
            'target_kits' => 100,
        ]);

        $school = School::create([
            'campaign_id' => $campaign->id,
            'official_name' => 'Test School',
            'address' => 'Addr',
            'access_token' => 'tok',
        ]);

        $structure = Structure::create([
            'school_id' => $school->id,
            'name' => 'Test Structure',
            'address' => 'Test Address',
        ]);

        $this->assertTrue($school->structures->contains($structure));
        $this->assertTrue(strlen($structure->id) === 36);
    }

    public function test_child_names_are_formatted()
    {
        $campaign = Campaign::create(['name'=>'C', 'month_year_suffix'=>'S', 'target_kits'=>10]);
        $school = School::create(['campaign_id'=>$campaign->id, 'official_name'=>'S', 'address'=>'A', 'access_token'=>'T']);
        $structure = Structure::create(['school_id'=>$school->id, 'name'=>'S', 'address'=>'A']);
        $group = Group::create(['structure_id'=>$structure->id, 'name'=>'G', 'educator_name'=>'E']);

        $child = Child::create([
            'group_id' => $group->id,
            'child_full_name' => 'john doe',
            'parent_full_name' => 'jane doe',
        ]);

        $this->assertEquals('JOHN DOE', $child->child_full_name);
        $this->assertEquals('Jane Doe', $child->parent_full_name);
    }
}
