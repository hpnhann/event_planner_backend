<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\AttendanceController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public - Get all events
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Events (create, update, delete require auth)
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    
    // Attendance
    Route::post('/events/{eventId}/register', [AttendanceController::class, 'registerForEvent']);
    Route::post('/events/{eventId}/checkin', [AttendanceController::class, 'checkIn']);
    Route::delete('/events/{eventId}/register', [AttendanceController::class, 'cancelRegistration']);
    Route::get('/events/{eventId}/attendees', [AttendanceController::class, 'getAttendees']);
    Route::get('/my-events', [AttendanceController::class, 'myEvents']);
});


// Update code ngày 24/11