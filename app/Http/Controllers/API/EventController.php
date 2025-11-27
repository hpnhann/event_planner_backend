<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Display a listing of events
     * GET /api/v1/events
     */
    public function index(Request $request)
    {
        try {
            $query = Event::with(['creator']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by upcoming events
            if ($request->has('upcoming') && $request->upcoming == 'true') {
                $query->where('start_date', '>', now());
            }

            // Search by title
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            $perPage = $request->get('per_page', 10);
            $events = $query->orderBy('start_date', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Events retrieved successfully',
                'data' => $events
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch events',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created event
     * POST /api/v1/events
     * Uses StoreEventRequest for validation
     */
    public function store(StoreEventRequest $request)
    {
        try {
            // Data đã được validate tự động bởi StoreEventRequest
            $validated = $request->validated();
            
            // Set default status if not provided (đã handle trong prepareForValidation)
            $validated['status'] = $validated['status'] ?? 'draft';
            
            // Create event
            $event = Event::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event->load('creator')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified event
     * GET /api/v1/events/{id}
     */
    public function show($id)
    {
        try {
            $event = Event::with(['creator', 'attendances.user', 'eventAssignments.user'])
                ->findOrFail($id);

            // Add participant count
            $event->participant_count = $event->eventAssignments()->count();

            return response()->json([
                'success' => true,
                'message' => 'Event retrieved successfully',
                'data' => $event
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified event
     * PUT/PATCH /api/v1/events/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:draft,published,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $event = Event::findOrFail($id);
            $event->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event->load('creator')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified event
     * DELETE /api/v1/events/{id}
     */
    public function destroy($id)
    {
        try {
            $event = Event::findOrFail($id);
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register user to event
     * POST /api/v1/events/{id}/register
     */
    public function register(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_id' => 'nullable|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $event = Event::findOrFail($id);

            // Check if event is published
            if ($event->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot register for unpublished event'
                ], 400);
            }

            // Check if already registered
            $exists = EventAssignment::where('event_id', $id)
                ->where('user_id', $request->user_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already registered for this event'
                ], 409);
            }

            // Check max participants
            if ($event->max_participants) {
                $currentCount = EventAssignment::where('event_id', $id)->count();
                if ($currentCount >= $event->max_participants) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Event is full'
                    ], 400);
                }
            }

            // Create assignment
            $assignment = EventAssignment::create([
                'event_id' => $id,
                'user_id' => $request->user_id,
                'role_id' => $request->role_id,
                'status' => 'registered'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully registered for event',
                'data' => $assignment->load(['event', 'user', 'role'])
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
     * Unregister user from event
     * POST /api/v1/events/{id}/unregister
     */
    public function unregister(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignment = EventAssignment::where('event_id', $id)
                ->where('user_id', $request->user_id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not registered for this event'
                ], 404);
            }

            $assignment->delete();

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
     * GET /api/v1/events/{id}/participants
     */
    public function getParticipants($id)
    {
        try {
            $event = Event::findOrFail($id);
            
            $participants = EventAssignment::with(['user', 'role'])
                ->where('event_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Participants retrieved successfully',
                'data' => [
                    'event' => $event,
                    'participants' => $participants,
                    'total' => $participants->count(),
                    'max_participants' => $event->max_participants
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