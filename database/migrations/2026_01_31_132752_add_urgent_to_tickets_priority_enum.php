<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE tickets SET priority = 'high' WHERE priority = 'urgent'");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN priority ENUM('low', 'medium', 'high') DEFAULT 'medium'");
        }
    }
};
