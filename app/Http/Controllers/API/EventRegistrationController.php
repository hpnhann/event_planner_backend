<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class EventRegistrationController extends Controller
{
    /**
     * Register for event
     * POST /api/events/{eventId}/register
     */
    public function register(Request $request, $eventId)
    {
        try {
            $event = Event::findOrFail($eventId);

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

            // Check event status
            if ($event->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot register for unpublished event'
                ], 400);
            }

            $userId = Auth::user()->s_no;

            // Check if already registered
            $exists = EventRegistration::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already registered for this event'
                ], 409);
            }

            // Check max volunteers
            if ($event->max_volunteers) {
                $currentCount = EventRegistration::where('event_id', $eventId)
                    ->whereIn('status', ['pending', 'approved'])
                    ->count();
                    
                if ($currentCount >= $event->max_volunteers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Event is full'
                    ], 400);
                }
            }

            // Create registration
            $registration = EventRegistration::create([
                'event_id' => $eventId,
                'user_id' => $userId,
                'notes' => $request->notes,
                'status' => 'pending',
                'registration_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully registered for event',
                'data' => $registration->load(['event', 'user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register for event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unregister from event
     * DELETE /api/events/{eventId}/register
     */
    public function unregister($eventId)
    {
        try {
            $userId = Auth::user()->s_no;

            $registration = EventRegistration::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered for this event'
                ], 404);
            }

            $registration->delete();

            return response()->json([
                'success' => true,
                'message' => 'Successfully unregistered from event'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unregister from event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get event participants
     * GET /api/events/{eventId}/participants
     */
    public function getParticipants($eventId)
    {
        try {
            $event = Event::findOrFail($eventId);
            
            $participants = EventRegistration::with('user')
                ->where('event_id', $eventId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'event' => $event,
                    'participants' => $participants,
                    'total' => $participants->count(),
                    'max_volunteers' => $event->max_volunteers
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch participants',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}