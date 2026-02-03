<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Moraru Alin Eduard',
            'email' => 'alinmoraru980@gmail.com',
            'password' => bcrypt('PParolamea00'),
            'role' => 'admin',
            'job_title' => 'Facilitator',
        ]);

        \App\Models\Foundation::create([
            'name' => 'World Vision Romania',
            'details' => 'Start in Educatie Program',
        ]);
    }
}
