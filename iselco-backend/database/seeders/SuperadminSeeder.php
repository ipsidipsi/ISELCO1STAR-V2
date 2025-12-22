<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Superadmin Seeder
 * 
 * Creates the initial superadmin account for system access
 * 
 * Default credentials:
 * - Username: admin
 * - Mobile: Not set (can be added later)
 * - Password: admin123 (should be changed after first login)
 */
class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create superadmin user
        $superadmin = User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'), // Default password
            'employee_name' => 'System Administrator',
            'mobile_number' => null, // Can be set later
            'empbadge_number' => 'ADMIN001',
            'department_id' => null, // Not tied to specific department
            'is_active' => true,
            'must_change_password' => true, // Force password change on first login
        ]);

        // Assign Superadmin role
        $superadminRole = Role::where('slug', 'superadmin')->first();
        $superadmin->roles()->attach($superadminRole->id);

        $this->command->info('✅ Created superadmin account');
        $this->command->info('   Username: admin');
        $this->command->info('   Password: admin123');
        $this->command->warn('⚠️  Please change the password after first login!');
    }
}
