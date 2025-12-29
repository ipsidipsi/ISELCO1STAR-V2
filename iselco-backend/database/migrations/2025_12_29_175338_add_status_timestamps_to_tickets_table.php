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
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('assigned_at');
            $table->timestamp('resolved_at')->nullable()->after('started_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->timestamp('reopened_at')->nullable()->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'resolved_at', 'closed_at', 'reopened_at']);
        });
    }
};
