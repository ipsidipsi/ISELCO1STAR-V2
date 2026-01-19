<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Universal Broadcaster Role
        $universalRole = Role::firstOrCreate([
            'slug' => 'universal_broadcaster',
        ], [
            'name' => 'Universal Broadcaster',
            'description' => 'Can broadcast messages to ALL users in the system.',
            'is_system' => false
        ]);

        // Assign permission if exists
        $universalPerm = Permission::where('slug', 'broadcast.universal')->first();
        if ($universalPerm) {
            $universalRole->givePermissionTo('broadcast.universal');
             // Also give create permission just in case
            $universalRole->givePermissionTo('broadcast.create');
        }

        // 2. Create Department Broadcaster Role
        $deptBroadcasterRole = Role::firstOrCreate([
            'slug' => 'department_broadcaster',
        ], [
            'name' => 'Department Broadcaster',
            'description' => 'Can broadcast messages to their assigned departments.',
            'is_system' => false
        ]);

        $createPerm = Permission::where('slug', 'broadcast.create')->first();
        if ($createPerm) {
            $deptBroadcasterRole->givePermissionTo('broadcast.create');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('slug', 'universal_broadcaster')->delete();
        Role::where('slug', 'department_broadcaster')->delete();
    }
};
