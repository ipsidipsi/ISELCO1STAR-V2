<?php

namespace App\Console\Commands;

use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncDepartments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:departments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync departments from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting department sync...');

        try {
            // Fetch departments from external API
            $response = Http::timeout(30)->get('http://26.183.28.177:8000/api/employees/departments');

            if (!$response->successful()) {
                $this->error('❌ Failed to fetch departments from external API');
                Log::error('Department sync failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return 1;
            }

            $responseData = $response->json();
            
            // Check if response has the expected structure
            if (!isset($responseData['success']) || !isset($responseData['data'])) {
                $this->error('❌ Invalid response format from external API');
                return 1;
            }

            $externalDepartments = $responseData['data'];
            
            if (!is_array($externalDepartments)) {
                $this->error('❌ Invalid data format from external API');
                return 1;
            }

            $this->info("📥 Fetched " . count($externalDepartments) . " departments from external API");

            $syncedCount = 0;
            $createdCount = 0;
            $updatedCount = 0;
            $deactivatedCount = 0;

            // Track external department codes to identify removed departments
            $externalCodes = [];

            foreach ($externalDepartments as $departmentName) {
                // Clean up department name (remove \r\n and trim)
                $name = trim(str_replace(["\r", "\n"], '', $departmentName));
                
                if (empty($name)) {
                    $this->warn("⚠️  Skipping empty department name");
                    continue;
                }

                // Generate code from name (uppercase, replace spaces with underscores, limit to 50 chars)
                $code = strtoupper(str_replace(' ', '_', $name));
                $code = substr($code, 0, 50); // Ensure it fits in database

                $externalCodes[] = $code;

                // Find or create department
                $department = Department::where('code', $code)->first();

                if ($department) {
                    // Update existing department
                    $changed = false;

                    if ($department->name !== $name) {
                        $department->name = $name;
                        $changed = true;
                    }

                    if (!$department->is_active) {
                        $department->is_active = true;
                        $changed = true;
                    }

                    $department->synced_at = now();
                    $department->save();

                    if ($changed) {
                        $updatedCount++;
                        $this->line("  ✏️  Updated: {$code} - {$name}");
                    }
                } else {
                    // Create new department
                    Department::create([
                        'code' => $code,
                        'name' => $name,
                        'is_active' => true,
                        'synced_at' => now()
                    ]);

                    $createdCount++;
                    $this->line("  ✅ Created: {$code} - {$name}");
                }

                $syncedCount++;
            }

            // Deactivate departments not in external API anymore
            $removedDepartments = Department::where('is_active', true)
                ->whereNotIn('code', $externalCodes)
                ->get();

            foreach ($removedDepartments as $dept) {
                $dept->is_active = false;
                $dept->synced_at = now();
                $dept->save();

                $deactivatedCount++;
                $this->line("  ⚠️  Deactivated: {$dept->code} - {$dept->name}");
            }

            // Summary
            $this->newLine();
            $this->info("✅ Sync completed successfully!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Synced', $syncedCount],
                    ['Created', $createdCount],
                    ['Updated', $updatedCount],
                    ['Deactivated', $deactivatedCount],
                ]
            );

            Log::info('Department sync completed', [
                'synced' => $syncedCount,
                'created' => $createdCount,
                'updated' => $updatedCount,
                'deactivated' => $deactivatedCount
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            Log::error('Department sync exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
