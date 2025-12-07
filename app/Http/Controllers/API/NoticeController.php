<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoticeController extends Controller
{
    use ApiResponse;

    /**
     * Get all notices
     * GET /api/notices
     */
    public function index(Request $request)
    {
        $query = Notice::with('creator');

        // Public users only see published notices
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            $query->where('status', 'published');
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status (admin only)
        if ($request->has('status') && Auth::check() && Auth::user()->hasRole('admin')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $notices = $query->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse($notices, 'Notices retrieved successfully');
    }

    /**
     * Get single notice
     * GET /api/notices/{id}
     */
    public function show($id)
    {
        $notice = Notice::with('creator')->findOrFail($id);

        // Check permission
        if ($notice->status !== 'published' && 
            (!Auth::check() || 
             (!Auth::user()->hasRole('admin') && $notice->created_by !== Auth::id()))) {
            return $this->errorResponse('Notice not found', 404);
        }

        return $this->successResponse($notice, 'Notice retrieved successfully');
    }

    /**
     * Create notice (admin/organizer only)
     * POST /api/notices
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:general,urgent,event,academic',
            'status' => 'nullable|in:draft,published,archived',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'draft';

        if ($validated['status'] === 'published' && !isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $notice = Notice::create($validated);

        return $this->successResponse(
            $notice->load('creator'),
            'Notice created successfully',
            201
        );
    }

    /**
     * Update notice
     * PUT /api/notices/{id}
     */
    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        // Check permission
        $user = Auth::user();
        if ($notice->created_by !== $user->id && !$user->hasRole('admin')) {
            return $this->errorResponse('You do not have permission to update this notice', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:general,urgent,event,academic',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);

        // Set published_at when publishing
        if (isset($validated['status']) && $validated['status'] === 'published' && !$notice->published_at) {
            $validated['published_at'] = now();
        }

        $notice->update($validated);

        return $this->successResponse(
            $notice->load('creator'),
            'Notice updated successfully'
        );
    }

    /**
     * Delete notice
     * DELETE /api/notices/{id}
     */
    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);

        // Check permission
        $user = Auth::user();
        if ($notice->created_by !== $user->id && !$user->hasRole('admin')) {
            return $this->errorResponse('You do not have permission to delete this notice', 403);
        }

        $notice->delete();

        return $this->successResponse(null, 'Notice deleted successfully');
    }
}