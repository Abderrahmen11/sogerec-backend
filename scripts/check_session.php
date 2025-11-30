<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$session = DB::table('sessions')->orderBy('last_activity', 'desc')->first();
echo "Latest Session:\n";
echo json_encode($session, JSON_PRETTY_PRINT) . "\n";

echo "\n\nTotal active sessions: " . DB::table('sessions')->count() . "\n";
