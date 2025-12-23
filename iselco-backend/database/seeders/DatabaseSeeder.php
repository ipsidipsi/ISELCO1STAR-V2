<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 
     * Seeds in order:
     * 1. Roles and Permissions (RBAC foundation)
     * 2. Priorities (for tickets)
     * 3. Superadmin account (first user)
     */
    public function run(): void
    {
        // Seed RBAC system (roles and permissions)
        $this->call(RolePermissionSeeder::class);
        
        // Seed priorities (required before categories)
        $this->call(PrioritySeeder::class);
        
        // Seed sample departments
        $this->call(DepartmentSeeder::class);
        
        // Seed sample categories (requires departments and priorities)
        $this->call(CategorySeeder::class);
        
        // Create superadmin account
        $this->call(SuperadminSeeder::class);
        
        $this->command->info('');
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('✅ Sample data ready for testing');
        $this->command->info('💡 Later: Sync real departments/employees from external API');
    }
}
