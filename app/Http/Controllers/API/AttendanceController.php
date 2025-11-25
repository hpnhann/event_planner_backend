<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendances
     * GET /api/attendances
     */
    public function index(): JsonResponse
    {
        try {
            $attendances = Attendance::with(['event', 'user'])
                ->orderBy('check_in_time', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $attendances
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new attendance (Check-in)
     * POST /api/attendances
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'user_id' => 'required|exists:users,id',
            'check_in_time' => 'required|date',
            'status' => 'required|in:present,absent,late',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check duplicate check-in
            $exists = Attendance::where('event_id', $request->event_id)
                ->where('user_id', $request->user_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already checked in for this event'
                ], 409);
            }

            $attendance = Attendance::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful',
                'data' => $attendance->load(['event', 'user'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display specific attendance
     * GET /api/attendances/{id}
     */
    public function show(string $id): JsonResponse
    {
        try {
            $attendance = Attendance::with(['event', 'user'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $attendance
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance not found'
            ], 404);
        }
    }

    /**
     * Update attendance (e.g., add check-out time)
     * PUT/PATCH /api/attendances/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'check_out_time' => 'nullable|date|after:check_in_time',
            'status' => 'sometimes|in:present,absent,late',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attendance = Attendance::findOrFail($id);
            $attendance->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => $attendance->load(['event', 'user'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete attendance record
     * DELETE /api/attendances/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $attendance = Attendance::findOrFail($id);
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance'
            ], 500);
        }
    }

    /**
     * Get attendances by event
     * GET /api/events/{eventId}/attendances
     */
    public function getByEvent(string $eventId): JsonResponse
    {
        try {
            $attendances = Attendance::with('user')
                ->where('event_id', $eventId)
                ->orderBy('check_in_time', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $attendances
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendances'
            ], 500);
        }
    }

    /**
     * Get user's attendance history
     * GET /api/users/{userId}/attendances
     */
    public function getByUser(string $userId): JsonResponse
    {
        try {
            $attendances = Attendance::with('event')
                ->where('user_id', $userId)
                ->orderBy('check_in_time', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $attendances
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user attendances'
            ], 500);
        }
    }
}