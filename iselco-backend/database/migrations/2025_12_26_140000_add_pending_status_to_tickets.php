<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add 'pending' to status ENUM and convert existing 'new' and 'seen' to 'pending'
     */
    public function up(): void
    {
        // Step 1: Add 'pending' to the ENUM values
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('pending', 'new', 'seen', 'assigned', 'in_progress', 'resolved', 'closed', 'reopened') NOT NULL DEFAULT 'pending'");
        
        // Step 2: Update existing 'new' and 'seen' tickets to 'pending'
        DB::statement("UPDATE tickets SET status = 'pending' WHERE status IN ('new', 'seen')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'pending' back to 'new'
        DB::statement("UPDATE tickets SET status = 'new' WHERE status = 'pending'");
        
        // Remove 'pending' from ENUM (restore original)
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('new', 'seen', 'assigned', 'in_progress', 'resolved', 'closed', 'reopened') NOT NULL DEFAULT 'new'");
    }
};
