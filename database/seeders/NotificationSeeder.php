<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::all()->each(function ($user) {
            \App\Models\Notification::factory(rand(0, 5))->create([
                'notifiable_id' => $user->id,
                'notifiable_type' => \App\Models\User::class,
            ]);
        });
    }
}
