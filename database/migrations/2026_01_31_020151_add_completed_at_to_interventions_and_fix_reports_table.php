<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('interventions', 'completed_at')) {
            Schema::table('interventions', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->after('scheduled_at');
            });
        }

        if (!Schema::hasColumn('intervention_reports', 'technician_id')) {
            Schema::table('intervention_reports', function (Blueprint $table) {
                $table->foreignId('technician_id')->nullable()->after('intervention_id')->constrained('users')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('intervention_reports', 'report')) {
            Schema::table('intervention_reports', function (Blueprint $table) {
                $table->text('report')->nullable()->after('technician_id');
            });
        }
        if (!Schema::hasColumn('intervention_reports', 'worked_hours')) {
            Schema::table('intervention_reports', function (Blueprint $table) {
                $table->decimal('worked_hours', 6, 2)->nullable()->after('report');
            });
        }

        if (!Schema::hasTable('plannings')) {
            Schema::create('plannings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('intervention_id')->constrained()->onDelete('cascade');
                $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
                $table->date('planned_date');
                $table->string('status')->default('scheduled');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('interventions', 'completed_at')) {
            Schema::table('interventions', fn (Blueprint $t) => $t->dropColumn('completed_at'));
        }
        if (Schema::hasColumn('intervention_reports', 'technician_id')) {
            Schema::table('intervention_reports', fn (Blueprint $t) => $t->dropForeign(['technician_id']));
        }
        if (Schema::hasColumn('intervention_reports', 'report')) {
            Schema::table('intervention_reports', fn (Blueprint $t) => $t->dropColumn('report'));
        }
        if (Schema::hasColumn('intervention_reports', 'worked_hours')) {
            Schema::table('intervention_reports', fn (Blueprint $t) => $t->dropColumn('worked_hours'));
        }
        Schema::dropIfExists('plannings');
    }
};
