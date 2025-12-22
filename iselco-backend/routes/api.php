<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MetadataController;
use Illuminate\Http\Request;
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
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // Metadata (reference data for tickets)
    Route::get('/departments', [MetadataController::class, 'departments']);
    Route::get('/categories', [MetadataController::class, 'categories']);
    Route::get('/priorities', [MetadataController::class, 'priorities']);
    
    // Tickets (to be implemented)
    // Route::apiResource('tickets', TicketController::class);
    
    // Users (to be implemented)
    // Route::apiResource('users', UserController::class);
});
