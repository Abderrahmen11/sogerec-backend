<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InterventionSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Intervention::factory(15)->create()->each(function ($intervention) {
            \App\Models\InterventionReport::factory(rand(0, 1))->create(['intervention_id' => $intervention->id]);
        });
    }
}
