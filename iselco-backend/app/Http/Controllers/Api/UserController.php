<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * User Controller
 * 
 * Manages user accounts and administration
 * Supports RBAC and department-scoped access
 */
class UserController extends Controller
{
    /**
     * List users (with department filtering for dept admins)
     * 
     * GET /api/users?department_id=1&search=john
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'department']);

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Search by name, username, or employee name
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', "%{$request->search}%")
                  ->orWhere('employee_name', 'like', "%{$request->search}%")
                  ->orWhere('mobile_number', 'like', "%{$request->search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $users = $query->orderBy('employee_name')->paginate(50);

        return response()->json($users);
    }

    /**
     * Get single user
     * 
     * GET /api/users/{id}
     */
    public function show($id)
    {
        $user = User::with(['roles.permissions', 'department', 'supervisedDepartments'])
            ->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Create new user
     * 
     * POST /api/users
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'employee_name' => 'required|string',
            'mobile_number' => 'nullable|string|unique:users,mobile_number',
            'empbadge_number' => 'nullable|string|unique:users,empbadge_number',
            'department_id' => 'nullable|exists:departments,id',
            'password' => 'required|string|min:8',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Create user
        $user = User::create([
            'username' => $request->username,
            'employee_name' => $request->employee_name,
            'mobile_number' => $request->mobile_number,
            'empbadge_number' => $request->empbadge_number,
            'department_id' => $request->department_id,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'must_change_password' => true, // Force password change on first login
        ]);

        // Assign roles
        $user->roles()->attach($request->role_ids);

        // Load relationships for response
        $user->load(['roles', 'department']);

        return response()->json($user, 201);
    }

    /**
     * Update user
     * 
     * PATCH /api/users/{id}
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'employee_name' => 'sometimes|string',
            'mobile_number' => 'sometimes|nullable|string|unique:users,mobile_number,' . $id,
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Update basic fields
        $user->update($request->only(['employee_name', 'mobile_number', 'department_id']));

        // Update roles if provided
        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        $user->load(['roles', 'department']);

        return response()->json($user);
    }

    /**
     * Reset user password to "1234"
     * 
     * POST /api/users/{id}/reset-password
     */
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make('1234'),
            'must_change_password' => true,
        ]);

        return response()->json(['message' => 'Password reset to 1234. User must change on next login.']);
    }

    /**
     * Activate or deactivate user
     * 
     * PATCH /api/users/{id}/toggle-active
     */
    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return response()->json(['message' => "User {$status} successfully", 'user' => $user]);
    }

    /**
     * Delete user (soft delete)
     * 
     * DELETE /api/users/{id}
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
