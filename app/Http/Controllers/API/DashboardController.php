<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Models\Event;
use App\Models\Attendance;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get admin dashboard stats
     * GET /api/dashboard/admin
     */
    public function adminDashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_events' => Event::count(),
            'total_registrations' => EventRegistration::count(),
            'active_events' => Event::where('status', 'published')
                ->where('event_date', '>=', today())
                ->count(),
            'total_attendances' => Attendance::count(),
            
            // Users by role (using column, not relationship)
            'users_by_role' => User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get(),
            
            // Events by status
            'events_by_status' => Event::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            
            // Recent users
            'recent_users' => User::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['s_no', 'id', 'name', 'email', 'role', 'created_at']),
            
            // Recent events
            'recent_events' => Event::with('creator')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            
            // Upcoming events
            'upcoming_events' => Event::where('status', 'published')
                ->where('event_date', '>=', today())
                ->orderBy('event_date', 'asc')
                ->limit(5)
                ->get(),
        ];

        return $this->successResponse($stats, 'Admin dashboard data retrieved successfully');
    }

    /**
     * Get organizer dashboard stats
     * GET /api/dashboard/organizer
     */
    public function organizerDashboard()
    {
        $user = Auth::user();

        $stats = [
            'my_events' => Event::where('created_by', $user->id)->count(),
            'active_events' => Event::where('created_by', $user->id)
                ->where('status', 'published')
                ->where('event_date', '>=', today())
                ->count(),
            
            // Total registrations for my events
            'total_registrations' => EventRegistration::whereHas('event', function($q) use ($user) {
                $q->where('created_by', $user->id);
            })->count(),
            
            // Total check-ins for my events
            'total_attendances' => Attendance::whereHas('event', function($q) use ($user) {
                $q->where('created_by', $user->id);
            })->count(),
            
            // Checked in today
            'checked_in_today' => Attendance::whereHas('event', function($q) use ($user) {
                $q->where('created_by', $user->id);
            })
                ->where('status', 'present')
                ->whereDate('check_in_time', today())
                ->count(),
            
            // My events list
            'my_events_list' => Event::where('created_by', $user->id)
                ->withCount('registrations')
                ->withCount('attendances')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            
            // Upcoming events
            'upcoming_events' => Event::where('created_by', $user->id)
                ->where('status', 'published')
                ->where('event_date', '>=', today())
                ->orderBy('event_date', 'asc')
                ->limit(5)
                ->get(),
        ];

        return $this->successResponse($stats, 'Organizer dashboard data retrieved successfully');
    }

    /**
     * Get participant dashboard stats
     * GET /api/dashboard/participant
     */
    public function participantDashboard()
    {
        $user = Auth::user();
        $userId = $user->s_no;

        $stats = [
            // Events registered
            'registered_events' => EventRegistration::where('user_id', $userId)->count(),
            
            // Events attended (checked in)
            'attended_events' => Attendance::where('user_id', $userId)
                ->where('status', 'present')
                ->count(),
            
            // Upcoming registered events
            'upcoming_events' => EventRegistration::where('user_id', $userId)
                ->whereHas('event', function($q) {
                    $q->where('event_date', '>=', today())
                      ->where('status', 'published');
                })
                ->count(),
            
            // Streak (if exists)
            'current_streak' => $user->streak ? $user->streak->current_streak : 0,
            'longest_streak' => $user->streak ? $user->streak->longest_streak : 0,
            
            // My registrations
            'my_registrations' => EventRegistration::where('user_id', $userId)
                ->with('event.creator')
                ->orderBy('registration_date', 'desc')
                ->limit(10)
                ->get(),
            
            // Available events (not yet registered)
            'available_events' => Event::where('status', 'published')
                ->where('event_date', '>=', today())
                ->whereDoesntHave('registrations', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->orderBy('event_date', 'asc')
                ->limit(5)
                ->get(),
        ];

        return $this->successResponse($stats, 'Participant dashboard data retrieved successfully');
    }

    /**
     * Get general dashboard stats (based on user role)
     * GET /api/dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Check role directly from column (not relationship)
        if ($user->role === 'admin') {
            return $this->adminDashboard();
        } elseif ($user->role === 'organizer') {
            return $this->organizerDashboard();
        } else {
            return $this->participantDashboard();
        }
    }
}