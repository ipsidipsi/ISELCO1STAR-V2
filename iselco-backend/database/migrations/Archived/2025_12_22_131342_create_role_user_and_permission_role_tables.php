<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Role-User pivot
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->unique(['user_id', 'role_id']);
            $table->timestamps();
        });

        // Permission-Role pivot
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->unique(['role_id', 'permission_id']);
            $table->timestamps();
        });

        // Department-User pivot
        Schema::create('department_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->boolean('is_supervisor')->default(false);
            $table->unique(['user_id', 'department_id']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('department_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
    }
};
