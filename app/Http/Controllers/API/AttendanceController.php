<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Attendance;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Check-in to event
     * POST /api/events/{eventId}/checkin
     */
    public function checkIn(Request $request, $eventId)
    {
        try {
            $event = Event::findOrFail($eventId);
            $userId = Auth::user()->s_no;

            // Validate
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user registered for event
            $registration = EventRegistration::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must register for this event first'
                ], 400);
            }

            // Check if already checked in
            $existingAttendance = Attendance::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already checked in to this event'
                ], 409);
            }

            // Create attendance record
            $attendance = Attendance::create([
                'event_id' => $eventId,
                'user_id' => $userId,
                'status' => 'present',
                'check_in_time' => now(),
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully checked in to event',
                'data' => $attendance->load(['event', 'user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check-out from event
     * POST /api/events/{eventId}/checkout
     */
    public function checkOut($eventId)
    {
        try {
            $userId = Auth::user()->s_no;

            $attendance = Attendance::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not checked in to this event'
                ], 404);
            }

            if ($attendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already checked out'
                ], 409);
            }

            $attendance->update([
                'check_out_time' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully checked out from event',
                'data' => $attendance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get event attendees (who checked in)
     * GET /api/events/{eventId}/attendees
     */
    public function getAttendees($eventId)
    {
        try {
            $event = Event::findOrFail($eventId);
            
            $attendees = Attendance::with('user')
                ->where('event_id', $eventId)
                ->orderBy('check_in_time', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'event' => $event,
                    'attendees' => $attendees,
                    'total_attendees' => $attendees->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's events (registered + attended)
     * GET /api/my-events
     */
    public function myEvents(Request $request)
    {
        try {
            $userId = Auth::user()->s_no;
            $type = $request->query('type', 'all'); // all, registered, attended

            $data = [];

            if ($type === 'all' || $type === 'registered') {
                // Events user registered for
                $registrations = EventRegistration::with('event')
                    ->where('user_id', $userId)
                    ->orderBy('registration_date', 'desc')
                    ->get();
                
                $data['registered_events'] = $registrations;
            }

            if ($type === 'all' || $type === 'attended') {
                // Events user attended (checked in)
                $attendances = Attendance::with('event')
                    ->where('user_id', $userId)
                    ->orderBy('check_in_time', 'desc')
                    ->get();
                
                $data['attended_events'] = $attendances;
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch your events',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}