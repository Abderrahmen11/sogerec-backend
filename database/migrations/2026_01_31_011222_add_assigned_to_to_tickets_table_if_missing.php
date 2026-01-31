<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('tickets', 'assigned_to')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable();
        });

        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate key') === false) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('tickets', 'assigned_to')) {
            return;
        }

        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['assigned_to']);
            });
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), "Can't DROP") === false) {
                throw $e;
            }
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('assigned_to');
        });
    }
};
