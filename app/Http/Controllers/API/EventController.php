<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Get all events
     * GET /api/events
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
                $query->where('event_date', '>=', today());
            }

            // Search by title
            if ($request->has('search')) {
                $query->where('event_title', 'like', '%' . $request->search . '%');
            }

            $perPage = $request->get('per_page', 10);
            $events = $query->orderBy('event_date', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
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
     * Create event
     * POST /api/events
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'event_title' => 'required|string|max:255',
                'event_description' => 'nullable|string',
                'event_location' => 'nullable|string|max:255',
                'event_date' => 'required|date',
                'event_time' => 'nullable',
                'max_volunteers' => 'nullable|integer|min:1',
                'status' => 'nullable|in:draft,published',
            ]);

            $validated['status'] = $validated['status'] ?? 'draft';
            $validated['created_by'] = auth()->user()->id ?? null;

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
     * Get event detail
     * GET /api/events/{id}
     */
    public function show($id)
    {
        try {
            $event = Event::with(['creator', 'registrations.user', 'attendances'])
                ->findOrFail($id);

            $event->participant_count = $event->registrations()->count();
            
            return response()->json([
                'success' => true,
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
     * Update event
     * PUT /api/events/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'event_title' => 'sometimes|required|string|max:255',
            'event_description' => 'nullable|string',
            'event_date' => 'sometimes|required|date',
            'event_time' => 'nullable',
            'event_location' => 'nullable|string|max:255',
            'max_volunteers' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:draft,published',
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
     * Delete event
     * DELETE /api/events/{id}
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
}