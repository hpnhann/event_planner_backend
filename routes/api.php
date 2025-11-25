<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventAssignmentController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\StreakController;

/*
|--------------------------------------------------------------------------
| API Routes - Event Planner
|--------------------------------------------------------------------------
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Event Planner API',
        'version' => '1.0',
        'timestamp' => now()->toIso8601String()
    ]);
});

Route::prefix('v1')->group(function () {
    
    // ============================================
    // EVENTS
    // ============================================
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::patch('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    
    // Event registration endpoints
    Route::post('/events/{id}/register', [EventController::class, 'register']);
    Route::post('/events/{id}/unregister', [EventController::class, 'unregister']);
    Route::get('/events/{id}/participants', [EventController::class, 'getParticipants']);
    
    // ============================================
    // ATTENDANCES
    // ============================================
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::post('/attendances', [AttendanceController::class, 'store']);
    Route::get('/attendances/{id}', [AttendanceController::class, 'show']);
    Route::put('/attendances/{id}', [AttendanceController::class, 'update']);
    Route::patch('/attendances/{id}', [AttendanceController::class, 'update']);
    Route::delete('/attendances/{id}', [AttendanceController::class, 'destroy']);
    
    // Attendance by event/user
    Route::get('/events/{eventId}/attendances', [AttendanceController::class, 'getByEvent']);
    Route::get('/users/{userId}/attendances', [AttendanceController::class, 'getByUser']);
    
    // ============================================
    // EVENT ASSIGNMENTS
    // ============================================
    Route::get('/event-assignments', [EventAssignmentController::class, 'index']);
    Route::post('/event-assignments', [EventAssignmentController::class, 'store']);
    Route::get('/event-assignments/{id}', [EventAssignmentController::class, 'show']);
    Route::put('/event-assignments/{id}', [EventAssignmentController::class, 'update']);
    Route::patch('/event-assignments/{id}', [EventAssignmentController::class, 'update']);
    Route::delete('/event-assignments/{id}', [EventAssignmentController::class, 'destroy']);
    
    // Assignment actions
    Route::post('/event-assignments/{id}/confirm', [EventAssignmentController::class, 'confirm']);
    Route::post('/event-assignments/{id}/cancel', [EventAssignmentController::class, 'cancel']);
    Route::get('/events/{eventId}/assignments', [EventAssignmentController::class, 'getByEvent']);
    Route::get('/users/{userId}/assignments', [EventAssignmentController::class, 'getByUser']);
    
    // ============================================
    // ROLES
    // ============================================
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::patch('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    
    // ============================================
    // STREAKS
    // ============================================
    Route::get('/streaks', [StreakController::class, 'index']);
    Route::get('/streaks/leaderboard', [StreakController::class, 'leaderboard']);
    Route::get('/users/{userId}/streak', [StreakController::class, 'getUserStreak']);
    Route::post('/streaks/update', [StreakController::class, 'updateStreak']);
    Route::post('/users/{userId}/streak/reset', [StreakController::class, 'resetStreak']);
    
    // ============================================
    // USERS (Basic endpoints)
    // ============================================
    Route::get('/users/{id}', function($id) {
        try {
            $user = \App\Models\User::with(['eventAssignments', 'attendances', 'streak'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    });
});

/*
|--------------------------------------------------------------------------
| For Production: Wrap in Authentication Middleware
|--------------------------------------------------------------------------
| Route::middleware('auth:sanctum')->group(function () {
|     // All protected routes here
| });
*/