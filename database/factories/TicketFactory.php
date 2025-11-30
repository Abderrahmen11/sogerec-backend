<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'status' => 'open',
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'category' => fake()->randomElement(['bug', 'feature', 'support', 'maintenance']),
            'assigned_to' => null,
        ];
    }

    /**
     * State for open tickets
     */
    public function open(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'open',
        ]);
    }

    /**
     * State for in-progress tickets
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
            'assigned_to' => User::factory()->technician(),
        ]);
    }

    /**
     * State for closed tickets
     */
    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * State for high priority tickets
     */
    public function highPriority(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * State for assigned tickets
     */
    public function assignedToTechnician(User $technician = null): static
    {
        return $this->state(fn(array $attributes) => [
            'assigned_to' => $technician?->id ?? User::factory()->technician(),
        ]);
    }
}
