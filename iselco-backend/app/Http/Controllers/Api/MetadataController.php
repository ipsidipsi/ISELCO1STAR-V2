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
    public function departments()
    {
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($departments);
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
}
