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
        $currentUser = auth()->user();
        $query = User::with(['roles', 'department']);

        // Department Admin: Only see users in their department(s)
        if ($currentUser->hasRole('department_admin') && !$currentUser->hasRole('superadmin')) {
            $accessibleDepartmentIds = $currentUser->getAccessibleDepartmentIds();
            
            if (empty($accessibleDepartmentIds)) {
                // No departments accessible, return empty
                return response()->json(['data' => [], 'total' => 0]);
            }
            
            // Filter to only users in accessible departments
            $query->whereIn('department_id', $accessibleDepartmentIds);
            
            // Exclude superadmins from view
            $query->whereDoesntHave('roles', function($q) {
                $q->where('slug', 'superadmin');
            });
        }

        // Filter by department (if specified in request)
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
            'password' => 'required|string|min:4',
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
        $currentUser = auth()->user();

        // Department Admin restrictions
        if ($currentUser->hasRole('department_admin') && !$currentUser->hasRole('superadmin')) {
            // Cannot edit superadmins
            if ($user->hasRole('superadmin')) {
                return response()->json([
                    'error' => 'You do not have permission to edit this user.'
                ], 403);
            }

            // Cannot edit other department admins
            if ($user->hasRole('department_admin') && $user->id !== $currentUser->id) {
                return response()->json([
                    'error' => 'You cannot edit other department administrators.'
                ], 403);
            }

            // Can only edit users in their accessible departments
            $accessibleDepartmentIds = $currentUser->getAccessibleDepartmentIds();
            if (!in_array($user->department_id, $accessibleDepartmentIds)) {
                return response()->json([
                    'error' => 'You can only edit users in your department.'
                ], 403);
            }
        }

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
     * Check if current user can manage target user (for dept admins)
     */
    private function canManageUser($targetUser)
    {
        $currentUser = auth()->user();

        // Superadmin can manage everyone
        if ($currentUser->hasRole('superadmin')) {
            return true;
        }

        // Department admin restrictions
        if ($currentUser->hasRole('department_admin')) {
            // Cannot manage superadmins
            if ($targetUser->hasRole('superadmin')) {
                return false;
            }

            // Cannot manage other department admins
            if ($targetUser->hasRole('department_admin') && $targetUser->id !== $currentUser->id) {
                return false;
            }

            // Can only manage users in accessible departments
            $accessibleDepartmentIds = $currentUser->getAccessibleDepartmentIds();
            return in_array($targetUser->department_id, $accessibleDepartmentIds);
        }

        // Regular users cannot manage anyone
        return false;
    }

    /**
     * Reset user password to "1234"
     * 
     * POST /api/users/{id}/reset-password
     */
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);

        // Check authorization
        if (!$this->canManageUser($user)) {
            return response()->json([
                'error' => 'You do not have permission to reset this user\'s password.'
            ], 403);
        }

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

    /**
     * Assign roles to a user (permanent)
     * 
     * POST /api/users/{id}/assign-roles
     * Body: { role_ids: [1, 2, 3] }
     */
    public function assignRoles(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user->roles()->sync($request->role_ids);
        $user->load('roles');

        return response()->json([
            'message' => 'Roles assigned successfully',
            'user' => $user
        ]);
    }

    /**
     * Assign temporary role to a user (Officer in Charge)
     * 
     * POST /api/users/{id}/assign-temporary-role
     * Body: { role_id, expires_at, reason }
     */
    public function assignTemporaryRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'expires_at' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500',
        ]);

        // Check if temporary assignment already exists
        $existing = \DB::table('role_user_temporary')
            ->where('user_id', $user->id)
            ->where('role_id', $request->role_id)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'User already has this temporary role assigned'
            ], 400);
        }

        // Create temporary role assignment
        \DB::table('role_user_temporary')->insert([
            'user_id' => $user->id,
            'role_id' => $request->role_id,
            'assigned_by_user_id' => auth()->id(),
            'expires_at' => $request->expires_at,
            'reason' => $request->reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Temporary role assigned successfully',
            'expires_at' => $request->expires_at
        ]);
    }

    /**
     * Get user's active temporary roles
     * 
     * GET /api/users/{id}/temporary-roles
     */
    public function getTemporaryRoles($id)
    {
        $temporaryRoles = \DB::table('role_user_temporary')
            ->join('roles', 'role_user_temporary.role_id', '=', 'roles.id')
            ->join('users as assigners', 'role_user_temporary.assigned_by_user_id', '=', 'assigners.id')
            ->where('role_user_temporary.user_id', $id)
            ->where('role_user_temporary.expires_at', '>', now())
            ->select(
                'role_user_temporary.id',
                'roles.name as role_name',
                'roles.slug as role_slug',
                'role_user_temporary.expires_at',
                'role_user_temporary.reason',
                'assigners.employee_name as assigned_by',
                'role_user_temporary.created_at'
            )
            ->get();

        return response()->json($temporaryRoles);
    }

    /**
     * Revoke temporary role before expiry
     * 
     * DELETE /api/users/{id}/temporary-roles/{roleId}
     */
    public function revokeTemporaryRole($id, $roleId)
    {
        $deleted = \DB::table('role_user_temporary')
            ->where('user_id', $id)
            ->where('role_id', $roleId)
            ->where('expires_at', '>', now())
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Temporary role revoked successfully']);
        }

        return response()->json(['error' => 'Temporary role assignment not found'], 404);
    }

    /**
     * Assign user to departments (with optional expiry for temporary assignments)
     * 
     * POST /api/users/{id}/assign-departments
     * Body: { 
     *   departments: [
     *     { department_id: 1, is_supervisor: true, expires_at: null, reason: null },
     *     { department_id: 2, is_supervisor: false, expires_at: '2025-01-15', reason: 'Temporary coverage' }
     *   ]
     * }
     */
    public function assignDepartments(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'departments' => 'required|array',
            'departments.*.department_id' => 'required|exists:departments,id',
            'departments.*.is_supervisor' => 'boolean',
            'departments.*.expires_at' => 'nullable|date|after:now',
            'departments.*.reason' => 'nullable|string|max:500',
        ]);

        // Clear existing department assignments
        $user->departments()->detach();

        // Assign departments
        foreach ($request->departments as $dept) {
            $user->departments()->attach($dept['department_id'], [
                'is_supervisor' => $dept['is_supervisor'] ?? false,
                'expires_at' => $dept['expires_at'] ?? null,
                'assigned_by_user_id' => auth()->id(),
                'reason' => $dept['reason'] ?? null,
            ]);
        }

        $user->load('departments');

        return response()->json([
            'message' => 'Departments assigned successfully',
            'user' => $user
        ]);
    }

    /**
     * Get user's department assignments with expiry info
     * 
     * GET /api/users/{id}/department-assignments
     */
    public function getDepartmentAssignments($id)
    {
        $assignments = \DB::table('department_user')
            ->join('departments', 'department_user.department_id', '=', 'departments.id')
            ->leftJoin('users as assigners', 'department_user.assigned_by_user_id', '=', 'assigners.id')
            ->where('department_user.user_id', $id)
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                'department_user.is_supervisor',
                'department_user.expires_at',
                'department_user.reason',
                'assigners.employee_name as assigned_by',
                'department_user.created_at'
            )
            ->get();

        return response()->json($assignments);
    }

    /**
     * Suspend user account
     * 
     * POST /api/users/{id}/suspend
     * Body: { reason? }
     */
    public function suspend(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Check authorization
        if (!$this->canManageUser($user)) {
            return response()->json([
                'error' => 'You do not have permission to suspend this user.'
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user->suspend($request->reason, auth()->id());

        return response()->json([
            'message' => 'User suspended successfully',
            'user' => $user
        ]);
    }

    /**
     * Mark user as on leave
     * 
     * POST /api/users/{id}/mark-on-leave
     * Body: { reason? }
     */
    public function markOnLeave(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user->markOnLeave($request->reason, auth()->id());

        return response()->json([
            'message' => 'User marked as on leave',
            'user' => $user
        ]);
    }

    /**
     * Retire user (soft delete)
     * 
     * POST /api/users/{id}/retire
     * Body: { reason? }
     */
    public function retire(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent retiring yourself
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot retire your own account'], 400);
        }

        // Check authorization
        if (!$this->canManageUser($user)) {
            return response()->json([
                'error' => 'You do not have permission to retire this user.'
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user->retire($request->reason, auth()->id());

        return response()->json([
            'message' => 'User retired successfully. Account has been soft deleted.',
            'user' => $user
        ]);
    }

    /**
     * Terminate user employment (soft delete)
     * 
     * POST /api/users/{id}/terminate
     * Body: { reason }
     */
    public function terminate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent terminating yourself
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot terminate your own account'], 400);
        }

        // Check authorization
        if (!$this->canManageUser($user)) {
            return response()->json([
                'error' => 'You do not have permission to terminate this user.'
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->terminate($request->reason, auth()->id());

        return response()->json([
            'message' => 'User terminated successfully. Account has been soft deleted.',
            'user' => $user
        ]);
    }

    /**
     * Reactivate user account
     * 
     * POST /api/users/{id}/reactivate
     * Body: { reason? }
     */
    public function reactivate(Request $request, $id)
    {
        // Include soft deleted users in search
        $user = User::withTrashed()->findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user->reactivate($request->reason, auth()->id());

        return response()->json([
            'message' => 'User reactivated successfully',
            'user' => $user
        ]);
    }

    /**
     * Get user status history
     * 
     * GET /api/users/{id}/status-history
     */
    public function getStatusHistory($id)
    {
        // This would require an audit log table
        // For now, return current status info
        $user = User::withTrashed()->findOrFail($id);

        return response()->json([
            'current_status' => $user->status,
            'status_reason' => $user->status_reason,
            'status_changed_at' => $user->status_changed_at,
            'status_changed_by' => $user->statusChangedBy ? [
                'id' => $user->statusChangedBy->id,
                'name' => $user->statusChangedBy->employee_name
            ] : null,
            'is_soft_deleted' => $user->trashed(),
            'deleted_at' => $user->deleted_at
        ]);
    }
}
