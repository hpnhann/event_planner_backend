<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventRegistrationController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\NoticeController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Events
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);

// Public Notices
Route::get('/notices', [NoticeController::class, 'index']);
Route::get('/notices/{id}', [NoticeController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Authenticated Users)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Dashboard (role-based)
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard'])
        ->middleware('role:admin');
    Route::get('/dashboard/organizer', [DashboardController::class, 'organizerDashboard'])
        ->middleware('role:organizer');
    Route::get('/dashboard/participant', [DashboardController::class, 'participantDashboard'])
        ->middleware('role:participant');
    
    // Events Management
    Route::post('/events', [EventController::class, 'store'])
        ->middleware('role:admin,organizer');
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    
    // Event Registration (FIX - Point to EventRegistrationController)
    Route::post('/events/{eventId}/register', [EventRegistrationController::class, 'register']);
    Route::delete('/events/{eventId}/register', [EventRegistrationController::class, 'unregister']);
    Route::get('/events/{eventId}/participants', [EventRegistrationController::class, 'getParticipants']);
    
    // Attendance (Check-in/Check-out)
    Route::post('/events/{eventId}/checkin', [AttendanceController::class, 'checkIn']);
    Route::post('/events/{eventId}/checkout', [AttendanceController::class, 'checkOut']); 
    Route::get('/events/{eventId}/attendees', [AttendanceController::class, 'getAttendees']);
    Route::get('/my-events', [AttendanceController::class, 'myEvents']);
    
    // Users Management (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
    // User Profile (self or admin)
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::get('/users/{id}/stats', [UserController::class, 'stats']);

    // Notices Management
    Route::post('/notices', [NoticeController::class, 'store'])
        ->middleware('role:admin,organizer');
    Route::put('/notices/{id}', [NoticeController::class, 'update']);
    Route::delete('/notices/{id}', [NoticeController::class, 'destroy']);
});