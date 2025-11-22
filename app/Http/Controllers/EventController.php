<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of events
     * GET /api/events
     */
    public function index(Request $request)
    {
        $query = Event::with(['creator', 'attendances']);

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

        // Pagination
        $perPage = $request->get('per_page', 10);
        $events = $query->orderBy('start_date', 'desc')->paginate($perPage);

        return $this->successResponse($events, 'Events retrieved successfully');
    }

    /**
     * Store a newly created event
     * POST /api/events
     * Only admin and organizer can create
     */
    public function store(Request $request)
    {
        // Check if user has permission
        $user = Auth::user();
        if (!$user->hasRole('admin') && !$user->hasRole('organizer')) {
            return $this->errorResponse('You do not have permission to create events', 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'nullable|in:draft,published,completed,cancelled',
        ]);

        $validated['created_by'] = $user->id;
        $validated['status'] = $validated['status'] ?? 'draft';

        $event = Event::create($validated);

        return $this->successResponse(
            $event->load('creator'),
            'Event created successfully',
            201
        );
    }

    /**
     * Display the specified event
     * GET /api/events/{id}
     */
    public function show($id)
    {
        $event = Event::with(['creator', 'attendances.user', 'assignments.user'])
            ->findOrFail($id);

        return $this->successResponse($event, 'Event retrieved successfully');
    }

    /**
     * Update the specified event
     * PUT /api/events/{id}
     * Only creator or admin can update
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $user = Auth::user();

        // Check permission
        if ($event->created_by !== $user->id && !$user->hasRole('admin')) {
            return $this->errorResponse('You do not have permission to update this event', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:draft,published,completed,cancelled',
        ]);

        $event->update($validated);

        return $this->successResponse(
            $event->load('creator'),
            'Event updated successfully'
        );
    }

    /**
     * Remove the specified event
     * DELETE /api/events/{id}
     * Only creator or admin can delete
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $user = Auth::user();

        // Check permission
        if ($event->created_by !== $user->id && !$user->hasRole('admin')) {
            return $this->errorResponse('You do not have permission to delete this event', 403);
        }

        $event->delete();

        return $this->successResponse(null, 'Event deleted successfully');
    }
}