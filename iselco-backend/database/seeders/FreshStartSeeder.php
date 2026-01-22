<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FreshStartSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate all tables
        $tables = [
            'users', 'roles', 'departments', 'department_user', 'categories',
            'priorities', 'tickets', 'ticket_timelines', 'comments', 'attachments',
            'ticket_activities', 'notifications', 'notification_preferences', 'personal_access_tokens',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        echo "✓ All tables truncated\n";

        // Create Roles (exactly 3: superadmin, department_admin, user)
        $superadminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Super Administrator',
            'slug' => 'superadmin',
            'description' => 'Full system access',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('roles')->insert([
            'name' => 'Department Admin',
            'slug' => 'department_admin',
            'description' => 'Department-level administrative access',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('roles')->insert([
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Regular user - can create and view tickets',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✓ Roles created (Superadmin, Department Admin, User)\n";

        // Create Departments
        $itDeptId = DB::table('departments')->insertGetId([
            'name' => 'Information Technology',
            'code' => 'IT',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('departments')->insert([
            ['name' => 'Human Resources', 'code' => 'HR', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Finance', 'code' => 'FIN', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        echo "✓ Departments created\n";

        // Create Categories
        DB::table('categories')->insert([
            ['name' => 'Hardware Issue', 'description' => 'Computer, printer, or hardware', 'department_id' => $itDeptId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Software Issue', 'description' => 'Application or software problems', 'department_id' => $itDeptId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Network Issue', 'description' => 'Internet or network problems', 'department_id' => $itDeptId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Account Access', 'description' => 'Login or account issues', 'department_id' => $itDeptId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        echo "✓ Categories created\n";

        // Create Priorities
        DB::table('priorities')->insert([
            ['name' => 'Low', 'level' => 1, 'color' => '#10b981', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Medium', 'level' => 2, 'color' => '#f59e0b', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'High', 'level' => 3, 'color' => '#ef4444', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Critical', 'level' => 4, 'color' => '#7c3aed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        echo "✓ Priorities created\n";

        // Create Superadmin User (minimal fields)
        DB::table('users')->insert([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'employee_name' => 'System Administrator',
            'department_id' => $itDeptId,
            'is_active' => true,
            'must_change_password' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✓ Superadmin user created\n\n";
        echo "========================================\n";
        echo "DATABASE RESET COMPLETE!\n";
        echo "========================================\n";
        echo "Login: admin / admin\n";
        echo "========================================\n";
    }
}
