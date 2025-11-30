<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Delete existing admin@example.com if it exists
DB::table('users')->where('email', 'admin@example.com')->delete();

// Create a fresh admin@example.com user with password 'password'
DB::table('users')->insert([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'role' => 'admin',
    'phone' => '1234567890',
    'email_verified_at' => now(),
    'password' => Hash::make('password'),
    'remember_token' => null,
    'created_at' => now(),
    'updated_at' => now(),
]);

$user = DB::table('users')->where('email', 'admin@example.com')->first();
echo json_encode($user, JSON_PRETTY_PRINT);
