<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Update department_user table
 * 
 * Adds support for temporary department assignments with expiry dates
 * Allows department admins to be temporarily assigned to additional departments
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_supervisor');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('reason')->nullable();
            
            // Index for efficient expiry queries
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'assigned_by_user_id', 'reason']);
        });
    }
};
