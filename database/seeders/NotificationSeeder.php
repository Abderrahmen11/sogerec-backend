<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Create different types of notifications based on user role
            if ($user->role === 'admin') {
                $this->createAdminNotifications($user);
            } elseif ($user->role === 'technician') {
                $this->createTechnicianNotifications($user);
            } else {
                $this->createClientNotifications($user);
            }
        }
    }

    private function createAdminNotifications($user)
    {
        $notifications = [
            [
                'type' => 'App\\Notifications\\TicketCreated',
                'data' => [
                    'title' => 'New Ticket Created',
                    'body' => 'A new maintenance request has been submitted by John Doe.',
                    'action_url' => '/tickets/1',
                    'ticket_id' => 1
                ]
            ],
            [
                'type' => 'App\\Notifications\\TicketStatusChanged',
                'data' => [
                    'title' => 'Ticket Status Updated',
                    'body' => 'Ticket #5 has been marked as resolved.',
                    'action_url' => '/tickets/5',
                    'ticket_id' => 5
                ]
            ],
            [
                'type' => 'success',
                'data' => [
                    'title' => 'System Update',
                    'body' => 'All systems are running smoothly. 15 tickets processed today.',
                ]
            ],
        ];

        $this->createNotifications($user, $notifications);
    }

    private function createTechnicianNotifications($user)
    {
        $notifications = [
            [
                'type' => 'App\\Notifications\\TicketAssigned',
                'data' => [
                    'title' => 'New Ticket Assigned',
                    'body' => 'You have been assigned to ticket #12: HVAC System Maintenance.',
                    'action_url' => '/tickets/12',
                    'ticket_id' => 12
                ]
            ],
            [
                'type' => 'App\\Notifications\\InterventionScheduled',
                'data' => [
                    'title' => 'Intervention Scheduled',
                    'body' => 'Your intervention for Building A is scheduled for tomorrow at 10:00 AM.',
                    'action_url' => '/interventions/3',
                    'intervention_id' => 3
                ]
            ],
            [
                'type' => 'warning',
                'data' => [
                    'title' => 'Urgent Task',
                    'body' => 'High priority ticket requires immediate attention.',
                    'action_url' => '/tickets/8',
                ]
            ],
        ];

        $this->createNotifications($user, $notifications);
    }

    private function createClientNotifications($user)
    {
        $notifications = [
            [
                'type' => 'App\\Notifications\\TicketCreated',
                'data' => [
                    'title' => 'Request Received',
                    'body' => 'Your maintenance request has been received and is being processed.',
                    'action_url' => '/tickets/7',
                    'ticket_id' => 7
                ]
            ],
            [
                'type' => 'App\\Notifications\\TicketAssigned',
                'data' => [
                    'title' => 'Technician Assigned',
                    'body' => 'A technician has been assigned to your request. Expected completion: 2 days.',
                    'action_url' => '/tickets/7',
                    'ticket_id' => 7
                ]
            ],
            [
                'type' => 'App\\Notifications\\InterventionScheduled',
                'data' => [
                    'title' => 'Intervention Scheduled',
                    'body' => 'Your maintenance work is scheduled for December 5th at 2:00 PM.',
                    'action_url' => '/interventions/5',
                    'intervention_id' => 5
                ]
            ],
            [
                'type' => 'App\\Notifications\\InterventionCompleted',
                'data' => [
                    'title' => 'Work Completed',
                    'body' => 'Your maintenance request has been completed successfully.',
                    'action_url' => '/tickets/7',
                    'ticket_id' => 7
                ]
            ],
        ];

        $this->createNotifications($user, $notifications);
    }

    private function createNotifications($user, $notifications)
    {
        foreach ($notifications as $index => $notificationData) {
            DatabaseNotification::create([
                'id' => Str::uuid(),
                'type' => $notificationData['type'],
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $user->id,
                'data' => $notificationData['data'],
                'read_at' => $index % 3 === 0 ? now() : null, // Mark some as read
                'created_at' => now()->subDays(rand(0, 7)),
                'updated_at' => now()->subDays(rand(0, 7)),
            ]);
        }
    }
}
