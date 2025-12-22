<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

/**
 * Priority Seeder
 * 
 * Seeds the database with default priority levels:
 * - Low (Level 1)
 * - Medium (Level 2)
 * - High (Level 3)
 * - Critical (Level 4)
 * 
 * Each priority has a color for UI display and optional SLA hours
 */
class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            [
                'name' => 'Low',
                'level' => 1,
                'color' => '#6c757d', // Gray
                'sla_hours' => 72, // 3 days
            ],
            [
                'name' => 'Medium',
                'level' => 2,
                'color' => '#0d6efd', // Blue
                'sla_hours' => 48, // 2 days
            ],
            [
                'name' => 'High',
                'level' => 3,
                'color' => '#ffc107', // Orange/Yellow
                'sla_hours' => 24, // 1 day
            ],
            [
                'name' => 'Critical',
                'level' => 4,
                'color' => '#dc3545', // Red
                'sla_hours' => 4, // 4 hours
            ],
        ];

        foreach ($priorities as $priorityData) {
            Priority::create($priorityData);
        }

        $this->command->info('✅ Created ' . count($priorities) . ' priority levels');
    }
}
