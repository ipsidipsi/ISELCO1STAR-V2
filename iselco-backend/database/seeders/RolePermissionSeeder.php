<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Role and Permission Seeder
 * 
 * Seeds the database with:
 * 1. Core system roles (Superadmin, Department Admin, User)
 * 2. Granular permissions organized by category
 * 3. Permission assignments to roles
 * 
 * This seeder creates the foundation for the RBAC system
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==================== CREATE PERMISSIONS ====================
        
        // Ticket Permissions
        $ticketPermissions = [
            ['name' => 'Create Tickets', 'slug' => 'tickets.create', 'description' => 'Can create new tickets', 'category' => 'Tickets'],
            ['name' => 'View Own Tickets', 'slug' => 'tickets.view.own', 'description' => 'Can view own created or assigned tickets', 'category' => 'Tickets'],
            ['name' => 'View Department Tickets', 'slug' => 'tickets.view.department', 'description' => 'Can view all tickets in assigned departments', 'category' => 'Tickets'],
            ['name' => 'View All Tickets', 'slug' => 'tickets.view.all', 'description' => 'Can view all tickets across all departments', 'category' => 'Tickets'],
            ['name' => 'Assign Tickets', 'slug' => 'tickets.assign', 'description' => 'Can assign tickets to users', 'category' => 'Tickets'],
            ['name' => 'Accept Tickets', 'slug' => 'tickets.accept', 'description' => 'Can accept open department tickets', 'category' => 'Tickets'],
            ['name' => 'Update Tickets', 'slug' => 'tickets.update', 'description' => 'Can update ticket details', 'category' => 'Tickets'],
            ['name' => 'Resolve Tickets', 'slug' => 'tickets.resolve', 'description' => 'Can mark tickets as resolved', 'category' => 'Tickets'],
            ['name' => 'Verify Tickets', 'slug' => 'tickets.verify', 'description' => 'Can verify and close resolved tickets', 'category' => 'Tickets'],
            ['name' => 'Reopen Tickets', 'slug' => 'tickets.reopen', 'description' => 'Can reopen closed tickets', 'category' => 'Tickets'],
            ['name' => 'Delete Tickets', 'slug' => 'tickets.delete', 'description' => 'Can delete tickets', 'category' => 'Tickets'],
        ];

        // User Management Permissions
        $userPermissions = [
            ['name' => 'View Users', 'slug' => 'users.view', 'description' => 'Can view user list', 'category' => 'Users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'description' => 'Can create new users', 'category' => 'Users'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'description' => 'Can update user details', 'category' => 'Users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'description' => 'Can delete users', 'category' => 'Users'],
            ['name' => 'Reset Passwords', 'slug' => 'users.reset_password', 'description' => 'Can reset user passwords', 'category' => 'Users'],
            ['name' => 'Manage Roles', 'slug' => 'users.manage_roles', 'description' => 'Can assign roles to users', 'category' => 'Users'],
        ];

        // Department Management Permissions
        $departmentPermissions = [
            ['name' => 'Manage Departments', 'slug' => 'departments.manage', 'description' => 'Can create, update, delete departments', 'category' => 'Departments'],
            ['name' => 'Sync Departments', 'slug' => 'departments.sync', 'description' => 'Can manually trigger department sync', 'category' => 'Departments'],
        ];

        // Metadata Management Permissions
        $metadataPermissions = [
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'description' => 'Can create, update, delete ticket categories', 'category' => 'Metadata'],
            ['name' => 'Manage Priorities', 'slug' => 'priorities.manage', 'description' => 'Can create, update, delete priorities', 'category' => 'Metadata'],
        ];

        // Reporting Permissions
        $reportPermissions = [
            ['name' => 'View Reports', 'slug' => 'reports.view', 'description' => 'Can view ticket reports and analytics', 'category' => 'Reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'description' => 'Can export reports to PDF/Excel', 'category' => 'Reports'],
        ];

        // System Settings Permissions
        $settingsPermissions = [
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'description' => 'Can modify system settings', 'category' => 'Settings'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'description' => 'Can create, update, delete roles', 'category' => 'Settings'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'description' => 'Can manage permission assignments', 'category' => 'Settings'],
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $ticketPermissions,
            $userPermissions,
            $departmentPermissions,
            $metadataPermissions,
            $reportPermissions,
            $settingsPermissions
        );

        foreach ($allPermissions as $permissionData) {
            Permission::create($permissionData);
        }

        // ==================== CREATE ROLES ====================

        // 1. Superadmin Role (System Role - Cannot be deleted)
        $superadminRole = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'superadmin',
            'description' => 'Full system access. Can manage all users, departments, and system settings.',
            'is_system' => true,
        ]);

        // Superadmin gets ALL permissions (assigned implicitly in User model)
        // No need to attach permissions - User->hasPermission() checks for superadmin first

        // 2. Department Admin Role (System Role - Cannot be deleted)
        $departmentAdminRole = Role::create([
            'name' => 'Department Administrator',
            'slug' => 'department_admin',
            'description' => 'Can manage users and tickets within assigned departments.',
            'is_system' => true,
        ]);

        // Department Admin permissions
        $departmentAdminPermissions = [
            // Tickets - Department scope
            'tickets.create',
            'tickets.view.department',
            'tickets.assign',
            'tickets.accept',
            'tickets.update',
            'tickets.resolve',
            
            // Users - Limited
            'users.view',
            'users.reset_password',
            
            // Reports
            'reports.view',
            'reports.export',
        ];

        foreach ($departmentAdminPermissions as $slug) {
            $departmentAdminRole->givePermissionTo($slug);
        }

        // 3. User Role (System Role - Cannot be deleted)
        $userRole = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Basic user role. Can create tickets and participate in assigned tickets.',
            'is_system' => true,
        ]);

        // User permissions
        $userPermissionSlugs = [
            'tickets.create',
            'tickets.view.own',
            'tickets.accept',
            'tickets.update',
            'tickets.resolve',
            'tickets.verify',
            'tickets.reopen',
        ];

        foreach ($userPermissionSlugs as $slug) {
            $userRole->givePermissionTo($slug);
        }

        // Output success message
        $this->command->info('✅ Created ' . count($allPermissions) . ' permissions');
        $this->command->info('✅ Created 3 system roles (Superadmin, Department Admin, User)');
        $this->command->info('✅ Assigned permissions to roles');
    }
}
