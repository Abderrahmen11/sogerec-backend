<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status VARCHAR(50)");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled') DEFAULT 'open'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_status_check");
            DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_status_check CHECK (status IN ('open', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'))");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status VARCHAR(50)");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'closed') DEFAULT 'open'");
        }
    }
};
