<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = DB::table('users')->where('role', 'admin')->first();
$ticket = DB::table('tickets')->first();
$intervention = DB::table('interventions')->first();
$notification = DB::table('notifications')->first();

echo "\n=== SEEDED DATA VERIFICATION ===\n";
echo "Admin User: " . $admin->name . " (" . $admin->email . ")\n";
echo "Sample Ticket: " . $ticket->title . " [" . $ticket->status . "]\n";
echo "Sample Intervention: " . $intervention->title . " [" . $intervention->status . "]\n";
echo "Sample Notification Type: " . $notification->type . "\n";

echo "\n=== RECORD COUNTS ===\n";
echo "Total Users: " . DB::table('users')->count() . "\n";
echo "  - Admins: " . DB::table('users')->where('role', 'admin')->count() . "\n";
echo "  - Technicians: " . DB::table('users')->where('role', 'technician')->count() . "\n";
echo "  - Regular Users: " . DB::table('users')->where('role', 'user')->count() . "\n";
echo "Total Tickets: " . DB::table('tickets')->count() . "\n";
echo "  - Open: " . DB::table('tickets')->where('status', 'open')->count() . "\n";
echo "  - In Progress: " . DB::table('tickets')->where('status', 'in_progress')->count() . "\n";
echo "  - Closed: " . DB::table('tickets')->where('status', 'closed')->count() . "\n";
echo "Total Interventions: " . DB::table('interventions')->count() . "\n";
echo "Total Notifications: " . DB::table('notifications')->count() . "\n";
echo "  - Unread: " . DB::table('notifications')->whereNull('read_at')->count() . "\n";
echo "  - Read: " . DB::table('notifications')->whereNotNull('read_at')->count() . "\n";
echo "\n";
