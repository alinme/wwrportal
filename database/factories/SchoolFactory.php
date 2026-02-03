<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'official_name' => $this->faker->company.' School',
            'address' => $this->faker->address,
            'access_token' => $this->faker->uuid,
            'campaign_id' => Campaign::factory(),
        ];
    }
}
