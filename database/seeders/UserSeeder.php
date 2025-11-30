<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);
        \App\Models\User::factory()->create(['email' => 'tech@example.com', 'role' => 'technician']);
        \App\Models\User::factory()->create(['email' => 'client@example.com', 'role' => 'client']);
        \App\Models\User::factory(10)->create();
    }
}
