<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Streak;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StreakController extends Controller
{
    /**
     * Get user's current streak
     * GET /api/v1/users/{userId}/streak
     */
    public function getUserStreak(string $userId): JsonResponse
    {
        try {
            $streak = Streak::where('user_id', $userId)
                ->with('user')
                ->first();

            if (!$streak) {
                // Create initial streak record
                $streak = Streak::create([
                    'user_id' => $userId,
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_attendance_date' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Streak retrieved successfully',
                'data' => $streak
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch streak',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update streak based on attendance
     * POST /api/v1/streaks/update
     */
    public function updateStreak(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'attendance_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->user_id;
            $attendanceDate = Carbon::parse($request->attendance_date)->startOfDay();

            $streak = Streak::firstOrCreate(
                ['user_id' => $userId],
                [
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_attendance_date' => null
                ]
            );

            $lastDate = $streak->last_attendance_date 
                ? Carbon::parse($streak->last_attendance_date)->startOfDay() 
                : null;

            if (!$lastDate) {
                // First attendance
                $streak->current_streak = 1;
                $streak->longest_streak = 1;
            } elseif ($attendanceDate->diffInDays($lastDate) == 1) {
                // Consecutive day
                $streak->current_streak += 1;
                $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
            } elseif ($attendanceDate->diffInDays($lastDate) > 1) {
                // Streak broken
                $streak->current_streak = 1;
            }
            // Same day = no change

            $streak->last_attendance_date = $attendanceDate;
            $streak->save();

            return response()->json([
                'success' => true,
                'message' => 'Streak updated successfully',
                'data' => $streak->load('user')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update streak',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get streak leaderboard
     * GET /api/v1/streaks/leaderboard
     */
    public function leaderboard(): JsonResponse
    {
        try {
            $leaderboard = Streak::with('user')
                ->orderBy('longest_streak', 'desc')
                ->orderBy('current_streak', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Leaderboard retrieved successfully',
                'data' => $leaderboard
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leaderboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display all streaks
     * GET /api/v1/streaks
     */
    public function index(): JsonResponse
    {
        try {
            $streaks = Streak::with('user')
                ->orderBy('current_streak', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Streaks retrieved successfully',
                'data' => $streaks
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch streaks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset user's streak
     * POST /api/v1/users/{userId}/streak/reset
     */
    public function resetStreak(string $userId): JsonResponse
    {
        try {
            $streak = Streak::where('user_id', $userId)->first();

            if (!$streak) {
                return response()->json([
                    'success' => false,
                    'message' => 'Streak not found for this user'
                ], 404);
            }

            $streak->update([
                'current_streak' => 0,
                'last_attendance_date' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Streak reset successfully',
                'data' => $streak
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset streak',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}