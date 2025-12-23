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

        return response()->json([
            'user' => $user,
            'token' => $token,
            'must_change_password' => $user->must_change_password,
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

        return response()->json([
            'user' => $user,
            'permissions' => $user->roles->flatMap->permissions->pluck('slug')->unique()->values(),
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
                'new_password' => ['Cannot use "1234" as your password. Please choose a different password.'],
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
            'username' => 'required|string',
            'employee_name' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'mobile_number' => 'nullable|string',
        ]);

        // Create password reset ticket
        $ticket = \App\Models\Ticket::create([
            'ticket_number' => 'PWD-' . date('Ymd') . '-' . str_pad(\App\Models\Ticket::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'title' => 'Password Reset Request',
            'description' => "Username: {$request->username}\nEmployee Name: {$request->employee_name}\nMobile Number: {$request->mobile_number}",
            'status' => 'new',
            'priority_id' => 4, // Critical
            'category_id' => 1, // Assuming "Password Reset" category exists
            'department_id' => $request->department_id,
            'requestor_id' => 1, // System user or find by username
        ]);

        return response()->json([
            'message' => 'Password reset request submitted. An administrator will process your request soon.',
            'ticket_number' => $ticket->ticket_number,
        ]);
    }
}
