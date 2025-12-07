<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Get all users (admin only)
     * GET /api/users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role (column, not relationship)
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->successResponse($users, 'Users retrieved successfully');
    }

    /**
     * Get single user
     * GET /api/users/{id}
     */
    public function show($id)
    {
        $user = User::with(['createdEvents', 'attendances.event', 'streak'])
            ->findOrFail($id);

        return $this->successResponse($user, 'User retrieved successfully');
    }

    /**
     * Create new user (admin only)
     * POST /api/users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,organizer,participant,teacher,student',
        ]);

        // Generate custom ID (like _sms format)
        $prefix = strtoupper(substr($validated['role'], 0, 1));
        $customId = $prefix . time();

        $user = User::create([
            'id' => $customId,
            'name' => $validated['name'],
            'full_name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'theme' => 'light',
        ]);

        return $this->successResponse(
            $user,
            'User created successfully',
            201
        );
    }

    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Check permission: user can update themselves or admin can update anyone
        $currentUser = Auth::user();
        if ($currentUser->s_no !== $user->s_no && !$currentUser->hasRole('admin')) {
            return $this->errorResponse('You do not have permission to update this user', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id . ',s_no',
            'password' => 'sometimes|required|string|min:8',
            'role' => 'sometimes|in:admin,organizer,participant,teacher,student',
        ]);

        if (isset($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        // Update full_name if name changed
        if (isset($validated['name'])) {
            $validated['full_name'] = $validated['name'];
        }

        $user->update($validated);

        return $this->successResponse(
            $user->fresh(),
            'User updated successfully'
        );
    }

    /**
     * Get user's created events
     * GET /api/users/{id}/events
     */
    public function getUserEvents($id)
    {
        $user = User::findOrFail($id);
        
        $events = $user->createdEvents()
            ->with(['eventAssignments', 'attendances'])
            ->orderBy('event_date', 'desc')
            ->paginate(10);

        return $this->successResponse($events, 'User events retrieved successfully');
    }

    /**
     * Get user's attendance history
     * GET /api/users/{id}/attendances
     */
    public function getUserAttendances($id)
    {
        $user = User::findOrFail($id);
        
        $attendances = $user->attendances()
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->successResponse($attendances, 'User attendances retrieved successfully');
    }

    /**
     * Delete user (admin only)
     * DELETE /api/users/{id}
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Cannot delete yourself
        if (Auth::user()->s_no === $user->s_no) {
            return $this->errorResponse('You cannot delete yourself', 400);
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Get user statistics
     * GET /api/users/{id}/stats
     */
    public function stats($id)
    {
        $user = User::with(['createdEvents', 'attendances', 'streak'])->findOrFail($id);

        $stats = [
            'events_created' => $user->createdEvents()->count(),
            'events_attended' => $user->attendances()->where('status', 'present')->count(),
            'events_registered' => $user->eventAssignments()->count(),
            'current_streak' => $user->streak ? $user->streak->current_streak : 0,
            'longest_streak' => $user->streak ? $user->streak->longest_streak : 0,
        ];

        return $this->successResponse($stats, 'User statistics retrieved successfully');
    }
}