<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'user',
            'phone' => fake()->phoneNumber(),
        ];
    }

    /**
     * State for admin users
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
            'name' => fake()->firstName() . ' (Admin)',
        ]);
    }

    /**
     * State for technician users
     */
    public function technician(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'technician',
            'name' => fake()->firstName() . ' (Tech)',
        ]);
    }

    /**
     * State for regular users
     */
    public function user(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'user',
        ]);
    }
}
