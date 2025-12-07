<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebEventController extends Controller
{
    /**
     * Get all events (public)
     * Từ: public_events.php
     */
    public function index(Request $request)
    {
        $query = Event::with('organizer:id,name,email');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $events = $query->orderBy('event_date', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'events' => $events,
        ]);
    }

    /**
     * Get event detail
     * Từ: event_detail.php
     */
    public function show($id)
    {
        $event = Event::with(['organizer:id,name,email', 'attendees'])
                     ->findOrFail($id);

        return response()->json([
            'success' => true,
            'event' => $event,
        ]);
    }

    /**
     * Create new event
     * Từ: register_event.php
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date',
            'location' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['organizer_id'] = $request->user()->id;

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('events', 'public');
            $validated['image'] = $path;
        }

        $event = Event::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'event' => $event,
        ], 201);
    }

    /**
     * Update event
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        // Check permission
        if ($event->organizer_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'event_date' => 'sometimes|date',
            'location' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'status' => 'sometimes|in:upcoming,ongoing,completed,cancelled',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            $path = $request->file('image')->store('events', 'public');
            $validated['image'] = $path;
        }

        $event->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'event' => $event,
        ]);
    }

    /**
     * Delete event
     */
    public function destroy(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        // Check permission
        if ($event->organizer_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete image
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
        ]);
    }

    /**
     * Register for event (student attendance)
     */
    public function register(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $user = $request->user();

        // Check if already registered
        if ($event->attendees()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already registered for this event',
            ], 400);
        }

        $event->attendees()->attach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Successfully registered for the event',
        ]);
    }
}