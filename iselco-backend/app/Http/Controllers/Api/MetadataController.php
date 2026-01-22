<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use Illuminate\Http\Request;

/**
 * Metadata Controller
 * 
 * Provides reference data for tickets: departments, categories, priorities
 */
class MetadataController extends Controller
{
    /**
     * Get all departments
     * 
     * GET /api/departments
     * Returns: List of departments synced from external API
     */
    public function departments(Request $request)
    {
        $query = Department::where('is_active', true)
            ->orderBy('name');

        // Filter by assigned departments if scoped
        if ($request->query('scope') === 'assigned') {
            $user = $request->user();
            
            // Superadmins can see all
            if (!$user->hasRole('superadmin') && !$user->hasRole('admin')) {
                // Get IDs from activeDepartments relation
                $deptIds = $user->activeDepartments->pluck('id');
                
                // If checking for reports, fallback to primary department if no active depts linked (edge case)
                if ($deptIds->isEmpty() && $user->department_id) {
                    $deptIds->push($user->department_id);
                }

                if ($deptIds->isNotEmpty()) {
                    $query->whereIn('id', $deptIds);
                } else {
                    // Start of fallback logic if totally empty
                    // $query->where('id', 0); // Force empty? 
                    // Or do nothing and show all? 
                    // Better to show nothing for security if scoped.
                     $query->where('id', -1);
                }
            }
        }

        return response()->json($query->get());
    }

    /**
     * Get categories (optionally filtered by department)
     * 
     * GET /api/categories?department_id=1
     * Returns: Global categories + department-specific categories
     */
    public function categories(Request $request)
    {
        $query = Category::where('is_active', true)
            ->with('priority');

        // If department_id provided, get global + department-specific categories
        if ($request->has('department_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('department_id') // Global categories
                  ->orWhere('department_id', $request->department_id); // Department-specific
            });
        }

        $categories = $query->orderBy('name')->get();

        return response()->json($categories);
    }

    /**
     * Get all priorities
     * 
     * GET /api/priorities
     * Returns: List of priority levels ordered by level
     */
    public function priorities()
    {
        $priorities = Priority::orderBy('level', 'desc')->get();

        return response()->json($priorities);
    }

    /**
     * Get department sync status
     * 
     * GET /api/admin/departments/sync-status
     * Returns: Department sync information with affected category counts
     */
    public function departmentSyncStatus()
    {
        $totalDepartments = Department::count();
        $activeDepartments = Department::where('is_active', true)->count();
        $inactiveDepartments = Department::where('is_active', false)->count();
        
        // Get last sync timestamp
        $lastSync = Department::max('synced_at');
        
        // Get inactive departments with affected category counts
        $inactiveDepartmentsWithCategories = Department::where('is_active', false)
            ->withCount(['categories' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();
        
        $affectedCategories = $inactiveDepartmentsWithCategories->sum('categories_count');

        return response()->json([
            'total_departments' => $totalDepartments,
            'active_departments' => $activeDepartments,
            'inactive_departments' => $inactiveDepartments,
            'last_synced_at' => $lastSync,
            'affected_categories' => $affectedCategories,
            'inactive_departments_detail' => $inactiveDepartmentsWithCategories
        ]);
    }

    /**
     * Manually trigger department sync
     * 
     * POST /api/admin/departments/sync
     * Returns: Sync results
     */
    public function syncDepartments()
    {
        try {
            // Run the sync command programmatically
            \Illuminate\Support\Facades\Artisan::call('sync:departments');
            
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            // Refresh counts
            $totalDepartments = Department::count();
            $activeDepartments = Department::where('is_active', true)->count();
            $lastSync = Department::max('synced_at');

            return response()->json([
                'message' => 'Department sync completed successfully',
                'total_departments' => $totalDepartments,
                'active_departments' => $activeDepartments,
                'last_synced_at' => $lastSync,
                'output' => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

