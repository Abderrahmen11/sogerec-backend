<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'type' => fake()->randomElement([
                'ticket_created',
                'ticket_assigned',
                'intervention_scheduled',
                'intervention_completed',
                'system_notification',
            ]),
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => User::factory(),
            'data' => [
                'message' => fake()->sentence(),
                'title' => fake()->words(3, true),
            ],
            'read_at' => fake()->optional(0.7)->dateTime(),
        ];
    }

    /**
     * State for unread notifications
     */
    public function unread(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * State for read notifications
     */
    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => now()->subHours(rand(1, 24)),
        ]);
    }

    /**
     * State for ticket notifications
     */
    public function ticketNotification(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => fake()->randomElement(['ticket_created', 'ticket_assigned']),
            'data' => [
                'message' => fake()->sentence(),
                'title' => 'Ticket Update',
            ],
        ]);
    }

    /**
     * State for intervention notifications
     */
    public function interventionNotification(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => fake()->randomElement(['intervention_scheduled', 'intervention_completed']),
            'data' => [
                'message' => fake()->sentence(),
                'title' => 'Intervention Update',
            ],
        ]);
    }
}
