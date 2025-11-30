<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Intervention;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()
            ->admin()
            ->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'phone' => '+1 (555) 123-4567',
            ]);

        // Create technicians
        $technicians = User::factory()
            ->technician()
            ->count(5)
            ->create();

        // Create regular users
        $users = User::factory()
            ->user()
            ->count(20)
            ->create();

        // All users including admin
        $allUsers = collect([$admin])->merge($users)->merge($technicians);

        // Create tickets assigned to regular users and admin
        $ticketCreators = $users->merge(collect([$admin]));
        $tickets = [];

        // Create 50 tickets with various statuses
        for ($i = 0; $i < 50; $i++) {
            $status = match ($i % 3) {
                0 => 'open',
                1 => 'in_progress',
                default => 'closed',
            };

            $ticket = Ticket::factory()
                ->state([
                    'user_id' => $ticketCreators->random()->id,
                    'status' => $status,
                ])
                ->create();

            // Assign some in-progress tickets to technicians
            if ($status === 'in_progress') {
                $ticket->update(['assigned_to' => $technicians->random()->id]);
            }

            $tickets[] = $ticket;
        }

        // Create 80 interventions linked to tickets and technicians
        for ($i = 0; $i < 80; $i++) {
            $ticket = Ticket::whereIn('id', array_map(fn($t) => $t->id, $tickets))
                ->inRandomOrder()
                ->first();

            $intervention = Intervention::factory()
                ->withTechnician($technicians->random())
                ->withTicket($ticket)
                ->create();
        }

        // Create 100 notifications for random users
        $notificationTypes = [
            ['type' => 'ticket_created', 'title' => 'New Ticket', 'prefix' => 'Your ticket has been created: '],
            ['type' => 'ticket_assigned', 'title' => 'Ticket Assigned', 'prefix' => 'A technician has been assigned: '],
            ['type' => 'intervention_scheduled', 'title' => 'Intervention Scheduled', 'prefix' => 'An intervention is scheduled: '],
            ['type' => 'intervention_completed', 'title' => 'Intervention Completed', 'prefix' => 'Your intervention is complete: '],
            ['type' => 'system_notification', 'title' => 'System Update', 'prefix' => 'System notification: '],
        ];

        for ($i = 0; $i < 100; $i++) {
            $notifType = fake()->randomElement($notificationTypes);
            $isRead = fake()->boolean(70); // 70% chance of being read

            Notification::factory()
                ->state([
                    'notifiable_id' => $allUsers->random()->id,
                    'type' => $notifType['type'],
                    'data' => [
                        'message' => $notifType['prefix'] . fake()->sentence(6),
                        'title' => $notifType['title'],
                    ],
                    'read_at' => $isRead ? now()->subHours(rand(1, 72)) : null,
                ])
                ->create();
        }
    }
}
