<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\MetadataController;
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
    // Authentication
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
    // Users - CRUD
    //------------------------------------------------------------
    Route::apiResource('users', UserController::class);
    
    // Users - Management methods
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
});
