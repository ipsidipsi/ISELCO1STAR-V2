<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

/**
 * Role Controller
 * 
 * Manages roles and permissions in the RBAC system
 * Superadmin-only access for role management
 */
class RoleController extends Controller
{
    /**
     * List all roles with their permissions
     * 
     * GET /api/roles
     */
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        
        return response()->json($roles);
    }

    /**
     * Get single role with details
     * 
     * GET /api/roles/{id}
     */
    public function show($id)
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);
        
        return response()->json($role);
    }

    /**
     * Create a new custom role
     * 
     * POST /api/roles
     * Body: { name, slug, description, permission_ids }
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        // Create role (custom roles have is_system = false)
        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_system' => false, // Custom role, can be deleted
        ]);

        // Attach permissions if provided
        if ($request->has('permission_ids')) {
            $role->permissions()->attach($request->permission_ids);
        }

        $role->load('permissions');

        return response()->json($role, 201);
    }

    /**
     * Update role details and permissions
     * 
     * PATCH /api/roles/{id}
     * Body: { name?, description?, permission_ids? }
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Prevent editing system roles' slug or name
        if ($role->is_system) {
            $request->validate([
                'description' => 'nullable|string',
                'permission_ids' => 'nullable|array',
                'permission_ids.*' => 'exists:permissions,id',
            ]);

            // Only allow updating description and permissions for system roles
            if ($request->has('description')) {
                $role->update(['description' => $request->description]);
            }
        } else {
            $request->validate([
                'name' => 'sometimes|string|max:255|unique:roles,name,' . $id,
                'slug' => 'sometimes|string|max:255|unique:roles,slug,' . $id,
                'description' => 'nullable|string',
                'permission_ids' => 'nullable|array',
                'permission_ids.*' => 'exists:permissions,id',
            ]);

            $role->update($request->only(['name', 'slug', 'description']));
        }

        // Update permissions if provided
        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        $role->load('permissions');

        return response()->json($role);
    }

    /**
     * Delete a custom role
     * 
     * DELETE /api/roles/{id}
     * 
     * Note: Cannot delete system roles (superadmin, department_admin, user)
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Prevent deleting system roles
        if ($role->is_system) {
            return response()->json([
                'error' => 'Cannot delete system roles'
            ], 403);
        }

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete role that is assigned to users. Please reassign users first.'
            ], 400);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get all available permissions
     * 
     * GET /api/roles/permissions
     */
    public function getPermissions()
    {
        $permissions = Permission::orderBy('category')->orderBy('name')->get();
        
        // Group by category
        $grouped = $permissions->groupBy('category');
        
        return response()->json($grouped);
    }
}
