<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Seed sample departments for ISELCO-I
     * These are placeholder departments until synced from external API
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'is_active' => true,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'is_active' => true,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'is_active' => true,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'is_active' => true,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'is_active' => true,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer Service',
                'code' => 'CS',
                'is_active' => true,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ETSD',
                'code' => 'ETSD',
                'is_active' => true,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('departments')->insert($departments);

        $this->command->info('✅ Sample departments created successfully!');
        $this->command->info('   - 6 departments added');
        $this->command->info('   - Ready for ticket creation testing');
    }
}
