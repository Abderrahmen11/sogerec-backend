<?php

// Load Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\DatabaseNotification;

echo "Starting verification...\n";

// 1. Create a test user
$user = User::create([
    'name' => 'Test User',
    'email' => 'test_' . time() . '@example.com',
    'password' => Hash::make('password'),
    'role' => 'client',
]);

echo "User created: {$user->id}\n";

// 2. Create a notification manually (simulating what a Notification class would do)
// We need to insert directly into the notifications table or use a Notification class.
// Let's insert directly to avoid creating a Notification class file for this test.

$id = \Illuminate\Support\Str::uuid();
\Illuminate\Support\Facades\DB::table('notifications')->insert([
    'id' => $id,
    'type' => 'App\Notifications\TestNotification',
    'notifiable_type' => 'App\Models\User',
    'notifiable_id' => $user->id,
    'data' => json_encode(['message' => 'Hello World']),
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Notification created: {$id}\n";

// 3. Test relationships
$user->refresh();

// Test unread count
$count = $user->unreadNotifications()->count();
echo "Unread count: {$count} (Expected: 1)\n";

if ($count !== 1) {
    echo "FAILED: Unread count mismatch.\n";
    exit(1);
}

// Test fetching notifications
$notifications = $user->notifications()->get();
echo "Fetched notifications count: {$notifications->count()} (Expected: 1)\n";

if ($notifications->count() !== 1) {
    echo "FAILED: Fetch mismatch.\n";
    exit(1);
}

// Test marking as read
$notification = $user->unreadNotifications()->where('id', $id)->first();
if ($notification) {
    $notification->markAsRead();
    echo "Notification marked as read.\n";
} else {
    echo "FAILED: Could not find notification to mark as read.\n";
    exit(1);
}

$newCount = $user->unreadNotifications()->count();
echo "New unread count: {$newCount} (Expected: 0)\n";

if ($newCount !== 0) {
    echo "FAILED: Mark as read failed.\n";
    exit(1);
}

// Cleanup
$user->notifications()->delete();
$user->delete();
echo "Cleanup done.\n";
echo "VERIFICATION SUCCESSFUL.\n";
