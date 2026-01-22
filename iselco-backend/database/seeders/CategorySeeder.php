<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Seed sample categories for ISELCO-I
     * Categories are linked to departments and have default priorities
     */
    public function run(): void
    {
        // Get department and priority IDs
        $itDept = DB::table('departments')->where('code', 'IT')->first();
        $engDept = DB::table('departments')->where('code', 'ENG')->first();
        $finDept = DB::table('departments')->where('code', 'FIN')->first();
        $hrDept = DB::table('departments')->where('code', 'HR')->first();
        $opsDept = DB::table('departments')->where('code', 'OPS')->first();
        $csDept = DB::table('departments')->where('code', 'CS')->first();
        
        $lowPriority = DB::table('priorities')->where('level', 1)->first();
        $normalPriority = DB::table('priorities')->where('level', 2)->first();
        $highPriority = DB::table('priorities')->where('level', 3)->first();
        $criticalPriority = DB::table('priorities')->where('level', 4)->first();

        $categories = [
            // IT Department Categories
            [
                'name' => 'System Issue',
                'description' => 'Software or system-related problems',
                'department_id' => $itDept->id,
                'priority_id' => $highPriority->id,
                'icon' => 'bug',
                'color' => '#EF4444',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Network Problem',
                'description' => 'Internet, WiFi, or network connectivity issues',
                'department_id' => $itDept->id,
                'priority_id' => $criticalPriority->id,
                'icon' => 'wifi',
                'color' => '#DC2626',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Software Request',
                'description' => 'Request for new software or application',
                'department_id' => $itDept->id,
                'priority_id' => $normalPriority->id,
                'icon' => 'download',
                'color' => '#3B82F6',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hardware Issue',
                'description' => 'Computer, printer, or equipment problems',
                'department_id' => $itDept->id,
                'priority_id' => $highPriority->id,
                'icon' => 'desktop',
                'color' => '#F59E0B',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Engineering Categories
            [
                'name' => 'Equipment Malfunction',
                'description' => 'Machinery or equipment not working properly',
                'department_id' => $engDept->id,
                'priority_id' => $criticalPriority->id,
                'icon' => 'construct',
                'color' => '#DC2626',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maintenance Request',
                'description' => 'Scheduled or urgent maintenance needed',
                'department_id' => $engDept->id,
                'priority_id' => $normalPriority->id,
                'icon' => 'build',
                'color' => '#10B981',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Finance Categories
            [
                'name' => 'Payment Issue',
                'description' => 'Problems with billing or payments',
                'department_id' => $finDept->id,
                'priority_id' => $highPriority->id,
                'icon' => 'card',
                'color' => '#EF4444',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Account Query',
                'description' => 'Questions about account or statements',
                'department_id' => $finDept->id,
                'priority_id' => $normalPriority->id,
                'icon' => 'help-circle',
                'color' => '#3B82F6',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // HR Categories
            [
                'name' => 'Leave Request',
                'description' => 'Request for vacation or sick leave',
                'department_id' => $hrDept->id,
                'priority_id' => $normalPriority->id,
                'icon' => 'calendar',
                'color' => '#8B5CF6',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Employee Concern',
                'description' => 'HR-related employee issues',
                'department_id' => $hrDept->id,
                'priority_id' => $highPriority->id,
                'icon' => 'people',
                'color' => '#F59E0B',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Operations Categories
            [
                'name' => 'Service Interruption',
                'description' => 'Power outage or service disruption',
                'department_id' => $opsDept->id,
                'priority_id' => $criticalPriority->id,
                'icon' => 'flash',
                'color' => '#DC2626',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Customer Service Categories
            [
                'name' => 'Customer Complaint',
                'description' => 'Customer service related complaint',
                'department_id' => $csDept->id,
                'priority_id' => $highPriority->id,
                'icon' => 'chatbubbles',
                'color' => '#EF4444',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'General Inquiry',
                'description' => 'General questions or information request',
                'department_id' => $csDept->id,
                'priority_id' => $lowPriority->id,
                'icon' => 'information-circle',
                'color' => '#6B7280',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Global Categories (no specific department)
            [
                'name' => 'Forgot Password',
                'description' => 'Password reset request',
                'department_id' => null, // Global category
                'priority_id' => $normalPriority->id,
                'icon' => 'key',
                'color' => '#14B8A6',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other',
                'description' => 'Issues not categorized above',
                'department_id' => null, // Global category
                'priority_id' => $normalPriority->id,
                'icon' => 'ellipsis-horizontal',
                'color' => '#6B7280',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('categories')->insert($categories);

        $this->command->info('✅ Sample categories created successfully!');
        $this->command->info('   - 15 categories added');
        $this->command->info('   - Linked to departments and priorities');
        $this->command->info('   - Ready for ticket creation!');
    }
}
