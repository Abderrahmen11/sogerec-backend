<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InterventionReport>
 */
class InterventionReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intervention_id' => \App\Models\Intervention::factory(),
            'content' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['draft', 'submitted', 'approved']),
        ];
    }
}
