<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city . ' Campaign',
            'facilitator_name' => $this->faker->name,
            'month_year_suffix' => '.' . date('m.Y'),
            'target_kits' => $this->faker->numberBetween(100, 1000),
            'is_active' => true,
        ];
    }
}
