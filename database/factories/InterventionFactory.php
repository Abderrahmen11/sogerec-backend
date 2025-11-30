<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterventionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory()->technician(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'status' => fake()->randomElement(['planned', 'in_progress', 'completed', 'cancelled']),
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 weeks'),
        ];
    }

    /**
     * State for planned interventions
     */
    public function planned(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'planned',
        ]);
    }

    /**
     * State for in-progress interventions
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * State for completed interventions
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * State for cancelled interventions
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * State with specific technician
     */
    public function withTechnician(User $technician): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $technician->id,
        ]);
    }

    /**
     * State with specific ticket
     */
    public function withTicket(Ticket $ticket): static
    {
        return $this->state(fn(array $attributes) => [
            'ticket_id' => $ticket->id,
        ]);
    }
}
