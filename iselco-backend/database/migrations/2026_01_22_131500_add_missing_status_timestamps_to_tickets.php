<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add missing status timestamp columns to tickets table
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (!Schema::hasColumn('tickets', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('assigned_at');
            }
            if (!Schema::hasColumn('tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('tickets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('resolved_at');
            }
            if (!Schema::hasColumn('tickets', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable()->after('closed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $columns = ['started_at', 'resolved_at', 'closed_at', 'reopened_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
