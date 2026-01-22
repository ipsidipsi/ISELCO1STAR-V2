<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Category Management Controller
 * 
 * Handles CRUD operations for ticket categories
 * Admin-only access (requires superadmin or department_admin role)
 */
class CategoryController extends Controller
{
    /**
     * List all categories with filtering
     * 
     * GET /api/admin/categories
     * Query params:
     *  - department_id: filter by department
     *  - is_active: filter by active status
     *  - orphaned: show only categories with inactive departments
     */
    public function index(Request $request)
    {
        $query = Category::with(['department', 'priority']);

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter orphaned categories (department is inactive)
        if ($request->boolean('orphaned')) {
            $query->whereHas('department', function ($q) {
                $q->where('is_active', false);
            });
        }

        $categories = $query->orderBy('name')->get();

        // Add computed field for department status
        $categories->each(function ($category) {
            $category->is_orphaned = $category->department && !$category->department->is_active;
        });

        return response()->json($categories);
    }

    /**
     * Create new category
     * 
     * POST /api/admin/categories
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($request) {
                    // Unique name per department (or globally if no department)
                    return $query->where('department_id', $request->department_id);
                }),
            ],
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'priority_id' => 'nullable|exists:priorities,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'department_id' => $request->department_id,
            'priority_id' => $request->priority_id,
            'icon' => $request->icon,
            'color' => $request->color,
            'is_active' => true,
        ]);

        $category->load(['department', 'priority']);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    /**
     * Get single category
     * 
     * GET /api/admin/categories/{id}
     */
    public function show($id)
    {
        $category = Category::with(['department', 'priority'])->findOrFail($id);
        $category->is_orphaned = $category->department && !$category->department->is_active;

        return response()->json($category);
    }

    /**
     * Update category
     * 
     * PUT/PATCH /api/admin/categories/{id}
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($request, $id) {
                    return $query->where('department_id', $request->department_id)
                                 ->where('id', '!=', $id);
                }),
            ],
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'priority_id' => 'nullable|exists:priorities,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->only([
            'name',
            'description',
            'department_id',
            'priority_id',
            'icon',
            'color',
            'is_active',
        ]));

        $category->load(['department', 'priority']);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Deactivate category (soft delete)
     * 
     * DELETE /api/admin/categories/{id}
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Soft delete: just mark as inactive
        $category->update(['is_active' => false]);

        return response()->json([
            'message' => 'Category deactivated successfully'
        ]);
    }

    /**
     * Get orphaned categories (linked to inactive departments)
     * 
     * GET /api/admin/categories/orphaned
     */
    public function orphaned()
    {
        $categories = Category::with(['department', 'priority'])
            ->where('is_active', true)
            ->whereHas('department', function ($q) {
                $q->where('is_active', false);
            })
            ->orderBy('name')
            ->get();

        $categories->each(function ($category) {
            $category->is_orphaned = true;
        });

        return response()->json([
            'count' => $categories->count(),
            'categories' => $categories
        ]);
    }

    /**
     * Bulk reassign categories to a new department
     * 
     * POST /api/admin/categories/bulk-reassign
     * Body: { category_ids: [1,2,3], new_department_id: 5 }
     */
    public function bulkReassign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'new_department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if new department is active
        $department = Department::find($request->new_department_id);
        if (!$department->is_active) {
            return response()->json([
                'message' => 'Cannot reassign to an inactive department'
            ], 422);
        }

        $updated = Category::whereIn('id', $request->category_ids)
            ->update(['department_id' => $request->new_department_id]);

        return response()->json([
            'message' => "Successfully reassigned {$updated} categories",
            'updated' => $updated
        ]);
    }

    /**
     * Convert category to global (remove department link)
     * 
     * POST /api/admin/categories/{id}/convert-to-global
     */
    public function convertToGlobal($id)
    {
        $category = Category::findOrFail($id);

        $category->update(['department_id' => null]);

        $category->load(['department', 'priority']);

        return response()->json([
            'message' => 'Category converted to global successfully',
            'category' => $category
        ]);
    }
}
