<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\MetadataController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PriorityController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

//============================================================
// PUBLIC ROUTES (No authentication required)
//============================================================

// Authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

//============================================================
// PROTECTED ROUTES (Sanctum authentication required)
//============================================================

Route::middleware('auth:sanctum')->group(function () {
    
    //------------------------------------------------------------
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    // Delete All - MUST come before {id} route
    Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll']);
    // Preferences - MUST come before {id} route  
    Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences']);
    Route::patch('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
    // Web Push - MUST come before {id} route
    Route::post('/notifications/web-push/subscribe', [NotificationController::class, 'subscribeWebPush']);
    // Smart Read - MUST come before {id} route
    Route::post('/notifications/mark-ticket-read/{ticketId}', [NotificationController::class, 'markTicketAsRead']);
    // Parameterized routes LAST
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Auth & Userntication
    //------------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    //------------------------------------------------------------
    // Metadata (reference data for tickets)
    //------------------------------------------------------------
    Route::get('/departments', [MetadataController::class, 'departments']);
    Route::get('/categories', [MetadataController::class, 'categories']);
    Route::get('/priorities', [MetadataController::class, 'priorities']);
    
    //------------------------------------------------------------
    // Tickets - Special endpoints (MUST come before apiResource)
    //------------------------------------------------------------
    Route::get('/tickets/stats', [TicketController::class, 'stats']);
    Route::get('/tickets/assigned-to-me', [TicketController::class, 'assignedToMe']);
    
    //------------------------------------------------------------
    // Tickets - CRUD
    //------------------------------------------------------------
    Route::apiResource('tickets', TicketController::class);
    
    // Tickets - Lifecycle methods
    Route::post('/tickets/{id}/accept', [TicketController::class, 'accept']);
    Route::post('/tickets/{id}/start', [TicketController::class, 'start']);
    Route::post('/tickets/{id}/resolve', [TicketController::class, 'resolve']);
    Route::post('/tickets/{id}/verify', [TicketController::class, 'verify']);
    Route::post('/tickets/{id}/reject', [TicketController::class, 'reject']);
    Route::post('/tickets/{id}/reopen', [TicketController::class, 'reopen']);
    Route::post('/tickets/{id}/reset-password', [TicketController::class, 'resetPassword']);
    
    // Tickets - Timeline/Activities
    Route::get('/tickets/{id}/activities', [TicketController::class, 'getActivities']);
    
    //------------------------------------------------------------
    // Comments (Real-time Chat)
    //------------------------------------------------------------
    Route::get('/tickets/{ticketId}/comments', [CommentController::class, 'index']);
    Route::post('/tickets/{ticketId}/comments', [CommentController::class, 'store']);
    Route::patch('/comments/{id}', [CommentController::class, 'update']);
    Route::patch('/comments/{id}/read', [CommentController::class, 'markAsRead']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
    
    //------------------------------------------------------------
    // Attachments (File Upload/Download)
    //------------------------------------------------------------
    Route::post('/attachments', [AttachmentController::class, 'store']);
    Route::get('/attachments/{id}/download', [AttachmentController::class, 'download']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);
    
    //------------------------------------------------------------
    // Roles & Permissions Management
    //------------------------------------------------------------
    Route::apiResource('roles', RoleController::class);
    Route::get('/roles/permissions/all', [RoleController::class, 'getPermissions']);
    
    //------------------------------------------------------------
    // Users - CRUD
    //------------------------------------------------------------
    Route::apiResource('users', UserController::class);
    
    // Users - Role Management
    Route::post('/users/{id}/assign-roles', [UserController::class, 'assignRoles']);
    Route::post('/users/{id}/assign-temporary-role', [UserController::class, 'assignTemporaryRole']);
    Route::get('/users/{id}/temporary-roles', [UserController::class, 'getTemporaryRoles']);
    Route::delete('/users/{id}/temporary-roles/{roleId}', [UserController::class, 'revokeTemporaryRole']);
    
    // Users - Department Management
    Route::post('/users/{id}/assign-departments', [UserController::class, 'assignDepartments']);
    Route::get('/users/{id}/department-assignments', [UserController::class, 'getDepartmentAssignments']);
    
    // Users - Account Management
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
    
    // Users - Status Management
    Route::post('/users/{id}/suspend', [UserController::class, 'suspend']);
    Route::post('/users/{id}/mark-on-leave', [UserController::class, 'markOnLeave']);
    Route::post('/users/{id}/retire', [UserController::class, 'retire']);
    Route::post('/users/{id}/terminate', [UserController::class, 'terminate']);
    Route::post('/users/{id}/reactivate', [UserController::class, 'reactivate']);
    Route::get('/users/{id}/status-history', [UserController::class, 'getStatusHistory']);

    //------------------------------------------------------------
    // Admin: Category & Priority Management
    //------------------------------------------------------------
    // Route::middleware('role:superadmin|department_admin')->group(function () {
        // Categories - Special endpoints MUST come before apiResource
        Route::get('/admin/categories/orphaned', [App\Http\Controllers\Api\CategoryController::class, 'orphaned']);
        Route::post('/admin/categories/bulk-reassign', [App\Http\Controllers\Api\CategoryController::class, 'bulkReassign']);
        Route::post('/admin/categories/{id}/convert-to-global', [App\Http\Controllers\Api\CategoryController::class, 'convertToGlobal']);
        
        // Categories - CRUD
        Route::apiResource('admin/categories', App\Http\Controllers\Api\CategoryController::class);
        
        // Department Management (Admin)
    Route::apiResource('admin/departments', \App\Http\Controllers\Api\DepartmentController::class);

    // Reports Module
    Route::get('/reports', [\App\Http\Controllers\Api\ReportController::class, 'index']);
    Route::get('/reports/analytics', [\App\Http\Controllers\Api\ReportController::class, 'analytics']);
    Route::get('/reports/export', [\App\Http\Controllers\Api\ReportController::class, 'export']);
        
        // Priorities - CRUD
        Route::apiResource('admin/priorities', App\Http\Controllers\Api\PriorityController::class);
        
        // Departments - Sync Status
        Route::get('/admin/departments/sync-status', [MetadataController::class, 'departmentSyncStatus']);
        
        // Departments - Manual Sync Trigger
        Route::post('/admin/departments/sync', [MetadataController::class, 'syncDepartments']);
    // });
});
