<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User Seeder
 * 
 * Creates department admin users for testing and initial setup
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get department admin role
        $deptAdminRole = Role::where('slug', 'department_admin')->first();

        if (!$deptAdminRole) {
            $this->command->error('Department admin role not found. Please run RolePermissionSeeder first.');
            return;
        }

        // Get departments (Finance and IT)
        $financeDept = Department::where('code', 'FIN')->orWhere('name', 'like', '%Finance%')->first();
        $itDept = Department::where('code', 'IT')->orWhere('name', 'like', '%IT%')->orWhere('name', 'like', '%Information%')->first();

        // Create Finance Admin
        if ($financeDept) {
            $financeAdmin = User::firstOrCreate(
                ['username' => 'financeadmin'],
                [
                    'password' => Hash::make('admin'),
                    'employee_name' => 'Finance Administrator',
                    'mobile_number' => null,
                    'empbadge_number' => 'FADM001',
                    'department_id' => $financeDept->id,
                    'is_active' => true,
                    'must_change_password' => true,
                ]
            );

            // Assign department admin role
            if (!$financeAdmin->hasRole('department_admin')) {
                $financeAdmin->roles()->attach($deptAdminRole->id);
            }

            // Assign to Finance department
            if (!$financeAdmin->departments()->where('department_id', $financeDept->id)->exists()) {
                $financeAdmin->departments()->attach($financeDept->id, [
                    'is_supervisor' => true,
                    'expires_at' => null,
                    'assigned_by_user_id' => 1, // Assigned by superadmin
                    'reason' => 'Permanent department admin'
                ]);
            }

            $this->command->info('✅ Created financeadmin user');
            $this->command->info('   Username: financeadmin');
            $this->command->info('   Password: admin');
            $this->command->info('   Department: ' . $financeDept->name);
        } else {
            $this->command->warn('⚠️  Finance department not found. Skipping financeadmin user.');
        }

        // Create IT Admin
        if ($itDept) {
            $itAdmin = User::firstOrCreate(
                ['username' => 'ITadmin'],
                [
                    'password' => Hash::make('admin'),
                    'employee_name' => 'IT Administrator',
                    'mobile_number' => null,
                    'empbadge_number' => 'ITADM001',
                    'department_id' => $itDept->id,
                    'is_active' => true,
                    'must_change_password' => true,
                ]
            );

            // Assign department admin role
            if (!$itAdmin->hasRole('department_admin')) {
                $itAdmin->roles()->attach($deptAdminRole->id);
            }

            // Assign to IT department
            if (!$itAdmin->departments()->where('department_id', $itDept->id)->exists()) {
                $itAdmin->departments()->attach($itDept->id, [
                    'is_supervisor' => true,
                    'expires_at' => null,
                    'assigned_by_user_id' => 1, // Assigned by superadmin
                    'reason' => 'Permanent department admin'
                ]);
            }

            $this->command->info('✅ Created ITadmin user');
            $this->command->info('   Username: ITadmin');
            $this->command->info('   Password: admin');
            $this->command->info('   Department: ' . $itDept->name);
        } else {
            $this->command->warn('⚠️  IT department not found. Skipping ITadmin user.');
        }

        $this->command->warn('⚠️  Please change passwords after first login!');
    }
}
