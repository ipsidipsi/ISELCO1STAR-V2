<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Controller
 * 
 * Handles user authentication using mobile_number instead of email
 * Uses Laravel Sanctum for SPA/mobile token authentication
 */
class AuthController extends Controller
{
    /**
     * Login with username OR mobile_number and password
     * 
     * POST /api/login
     * Body: { login, password }  (login can be username or mobile_number)
     * Returns: { user, token }
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find user by username OR mobile_number
        $user = User::where(function ($query) use ($request) {
                $query->where('username', $request->login)
                      ->orWhere('mobile_number', $request->login);
            })
            ->where('is_active', true)
            ->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Load relationships for response
        $user->load(['roles', 'department', 'supervisedDepartments']);

        // Create Sanctum token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Load all active roles (permanent + temporary)
        $allActiveRoles = $user->allActiveRoles()->load('permissions');

        return response()->json([
            'user' => $user,
            'token' => $token,
            'must_change_password' => $user->must_change_password,
            'all_roles' => $allActiveRoles, // Include temporary roles at login
        ]);
    }

    /**
     * Logout (revoke current token)
     * 
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        // Delete current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current authenticated user
     * 
     * GET /api/me
     * Returns: user with roles, permissions, departments
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['roles.permissions', 'department', 'supervisedDepartments']);

        // Get all active roles (permanent + temporary)
        $allActiveRoles = $user->allActiveRoles()->load('permissions');
        
        // Combine all permissions from both permanent and temporary roles
        $allPermissions = $allActiveRoles->flatMap->permissions->pluck('slug')->unique()->values();

        return response()->json([
            'user' => $user,
            'all_roles' => $allActiveRoles, // Include temporary roles for frontend
            'permissions' => $allPermissions,
        ]);
    }

    /**
     * Change password
     * 
     * POST /api/change-password
     * Body: { current_password (optional), new_password, new_password_confirmation }
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'nullable|string',
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        $user = $request->user();

        // Verify current password if provided
        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }
            
            // Ensure new password is different from current
            if ($request->current_password === $request->new_password) {
                throw ValidationException::withMessages([
                    'new_password' => ['The new password must be different from the current password.'],
                ]);
            }
        }

        // Prevent using '1234' as password - it's meant to be temporary only
        if ($request->new_password === '1234') {
            throw ValidationException::withMessages([
                'new_password' => ['1234 cant be use as password please input another'],
            ]);
        }

        // Update password and clear must_change flag
        $user->update([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Request password reset (creates a ticket)
     * 
     * POST /api/forgot-password
     * Body: { username, employee_name, department_id, mobile_number }
     * 
     * Note: This creates a password reset ticket for admin to process
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string|exists:users,username',
            'employee_name' => 'required|string',
            'mobile_number' => 'nullable|string',
        ]);

        // Find the user (now guaranteed to exist)
        $user = User::where('username', $request->username)->first();
        
        // Check for valid department. 
        // User reports that users table department_id is null, so we must use the relationship.
        $userDept = $user->activeDepartments()->first();
        $userDeptId = $userDept ? $userDept->id : null;

        // If user has no active department linked...
        if (!$userDeptId) {
             // Fallback: This is a data quality issue (User exists but has no dept).
             // We fallback to first department as a safety net.
             $fallbackDept = \App\Models\Department::first();
             $userDeptId = $fallbackDept ? $fallbackDept->id : null;
             
             if (!$userDeptId) {
                 return response()->json(['message' => 'System Error: No departments defined in the system. Please contact admin.'], 500);
             }
        }

        // Check for spam: Prevent multiple open password reset requests
        $pendingTicket = \App\Models\Ticket::where('requestor_id', $user->id)
            ->whereHas('category', function($q) {
                $q->where('name', 'Forgot Password');
            })
            ->whereIn('status', ['new', 'assigned', 'in_progress']) // Open statuses
            ->first();

        if ($pendingTicket) {
             return response()->json([
                 'message' => 'You already have a pending password reset request (Ticket #' . $pendingTicket->ticket_number . '). Please wait for it to be processed.',
                 'ticket_number' => $pendingTicket->ticket_number
             ], 429); // 429 Too Many Requests
        }

        // Find "Forgot Password" category
        // We moved this block back here because it was accidentally removed, causing "Undefined variable"
        $category = \App\Models\Category::firstOrCreate(
            ['name' => 'Forgot Password'],
            ['description' => 'Tickets for password reset requests', 'is_active' => true]
        );
        $categoryId = $category->id;

        $ticket = \App\Models\Ticket::create([
            'ticket_number' => 'PWD-' . date('Ymd') . '-' . str_pad(\App\Models\Ticket::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'title' => "Password reset request for user " . $request->employee_name . ", " . ($request->mobile_number ?? 'No Mobile'),
            'description' => "Username: {$request->username}\nEmployee Name: {$request->employee_name}\nMobile Number: {$request->mobile_number}\n\nRequesting password reset to default (1234).",
            'status' => 'new',
            'priority_id' => 4, // Critical
            'category_id' => $categoryId, 
            'department_id' => $userDeptId,
            'requestor_id' => $user ? $user->id : 1, // Link to actual user if found, else System User (1)
        ]);

        // Log activity and notify admins
        // This is crucial for alert notifications
        \App\Services\TicketActivityLogger::logCreated($ticket, $user ?: \App\Models\User::find(1));

        return response()->json([
            'message' => 'Password reset request submitted. An administrator will process your request soon.',
            'ticket_number' => $ticket->ticket_number,
        ]);
    }
}
