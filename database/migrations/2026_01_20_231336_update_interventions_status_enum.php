<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();

        // Only run raw ALTER statements on drivers that support MODIFY/ENUM syntax (MySQL)
        if ($driver === 'mysql') {
            // 1. Temporarily change to string to allow data manipulation
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE interventions MODIFY COLUMN status VARCHAR(255)");

            // 2. Convert old values to new ones
            \Illuminate\Support\Facades\DB::table('interventions')
                ->where('status', 'planned')
                ->update(['status' => 'scheduled']);

            // 3. Convert back to the new ENUM
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE interventions MODIFY COLUMN status ENUM('pending', 'scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
        } else {
            // For non-mysql (sqlite during tests, pgsql in some envs), perform safe updates only
            \Illuminate\Support\Facades\DB::table('interventions')
                ->where('status', 'planned')
                ->update(['status' => 'scheduled']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE interventions MODIFY COLUMN status VARCHAR(255)");

            \Illuminate\Support\Facades\DB::table('interventions')
                ->where('status', 'pending')
                ->orWhere('status', 'scheduled')
                ->update(['status' => 'planned']);

            \Illuminate\Support\Facades\DB::statement("ALTER TABLE interventions MODIFY COLUMN status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned'");
        } else {
            \Illuminate\Support\Facades\DB::table('interventions')
                ->where('status', 'pending')
                ->orWhere('status', 'scheduled')
                ->update(['status' => 'planned']);
        }
    }
};
