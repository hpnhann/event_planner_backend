<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Event;
use App\Models\Attendance;
use App\Models\Streak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator; 

class AttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Register for an event
     * POST /api/events/{eventId}/register
     */
    public function registerForEvent($eventId)
    {
        $user = Auth::user();
        $event = Event::findOrFail($eventId);

        // Check if event is published
        if ($event->status !== 'published') {
            return $this->errorResponse('This event is not open for registration', 400);
        }

        // Check if event has started
        if ($event->start_date < now()) {
            return $this->errorResponse('This event has already started', 400);
        }

        // Check if already registered
        $existingAttendance = Attendance::where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingAttendance) {
            return $this->errorResponse('You are already registered for this event', 400);
        }

        // Check max participants
        if ($event->max_participants) {
            $currentAttendees = Attendance::where('event_id', $eventId)->count();
            if ($currentAttendees >= $event->max_participants) {
                return $this->errorResponse('This event is full', 400);
            }
        }

        // Create attendance
        $attendance = Attendance::create([
            'event_id' => $eventId,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);

        return $this->successResponse(
            $attendance->load('event', 'user'),
            'Successfully registered for the event',
            201
        );
    }

    /**
     * Check-in to an event
     * POST /api/events/{eventId}/checkin
     */
    public function checkIn($eventId)
    {
        $user = Auth::user();
        $event = Event::findOrFail($eventId);

        // Find attendance record
        $attendance = Attendance::where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->first();

        if (!$attendance) {
            return $this->errorResponse('You are not registered for this event', 400);
        }

        // Check if already checked in
        if ($attendance->status === 'checked_in') {
            return $this->errorResponse('You have already checked in', 400);
        }

        // Check if event is happening (within event time range)
        $now = now();
        $startTime = Carbon::parse($event->start_date);
        $endTime = Carbon::parse($event->end_date);

        // Allow check-in 30 minutes before event starts
        if ($now->lt($startTime->subMinutes(30))) {
            return $this->errorResponse('Check-in is not yet available', 400);
        }

        if ($now->gt($endTime)) {
            return $this->errorResponse('This event has ended', 400);
        }

        // Update attendance
        $attendance->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        // Update streak
        $this->updateStreak($user->id);

        return $this->successResponse(
            $attendance->load('event', 'user'),
            'Successfully checked in to the event'
        );
    }

    /**
     * Get attendees of an event
     * GET /api/events/{eventId}/attendees
     */
    public function getAttendees($eventId)
    {
        $event = Event::findOrFail($eventId);

        $attendances = Attendance::with('user')
            ->where('event_id', $eventId)
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_registered' => $attendances->count(),
            'checked_in' => $attendances->where('status', 'checked_in')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
        ];

        return $this->successResponse([
            'attendees' => $attendances,
            'stats' => $stats,
        ], 'Attendees retrieved successfully');
    }

    /**
     * Cancel registration
     * DELETE /api/events/{eventId}/register
     */
    public function cancelRegistration($eventId)
    {
        $user = Auth::user();

        $attendance = Attendance::where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->first();

        if (!$attendance) {
            return $this->errorResponse('You are not registered for this event', 400);
        }

        // Can't cancel if already checked in
        if ($attendance->status === 'checked_in') {
            return $this->errorResponse('Cannot cancel after check-in', 400);
        }

        $attendance->delete();

        return $this->successResponse(null, 'Registration cancelled successfully');
    }

    /**
     * Get user's registered events
     * GET /api/my-events
     */
    public function myEvents()
    {
        $user = Auth::user();

        $attendances = Attendance::with('event.creator')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($attendances, 'Your events retrieved successfully');
    }

    /**
     * Update streak when user checks in
     */
    private function updateStreak($userId)  
    {
        $streak = Streak::firstOrCreate(['user_id' => $userId]);

        $today = now()->startOfDay();
        $lastAttendance = $streak->last_attendance_date 
            ? Carbon::parse($streak->last_attendance_date)->startOfDay() 
            : null;

        // If checked in today already, don't update
        if ($lastAttendance && $lastAttendance->eq($today)) {
            return;
        }

        // If last attendance was yesterday, increment streak
        if ($lastAttendance && $lastAttendance->eq($today->copy()->subDay())) {
            $streak->current_streak += 1;
        } else {
            // Reset streak if more than 1 day gap
            $streak->current_streak = 1;
        }

        // Update longest streak if needed
        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }

        $streak->last_attendance_date = $today;
        $streak->save();
    }
}