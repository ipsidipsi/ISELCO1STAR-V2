<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add status field to users table
 * 
 * Tracks employment status for better user management
 * - active: Currently employed and active
 * - suspended: Temporarily disabled (can be reactivated)
 * - on_leave: On approved leave
 * - retired: Permanently retired (soft deleted)
 * - terminated: Employment terminated (soft deleted)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'suspended', 'on_leave', 'retired', 'terminated'])
                ->default('active')
                ->after('is_active');
            
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_reason');
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->onDelete('set null')->after('status_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'status_reason', 'status_changed_at', 'status_changed_by']);
        });
    }
};
