<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create role_user_temporary table
 * 
 * Stores temporary role assignments with expiry dates
 * Use case: Officer in Charge (OIC) when department admin is on leave
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_user_temporary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('expires_at');
            $table->text('reason')->nullable(); // e.g., "OIC while Finance Admin on leave"
            $table->timestamps();
            
            // Index for efficient queries
            $table->index(['user_id', 'expires_at']);
            $table->index('expires_at'); // For scheduled cleanup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user_temporary');
    }
};
