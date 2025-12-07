<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Đọc role trực tiếp từ column 'role' trong users table
        $userRole = $request->user()->role;

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this resource',
                'required_roles' => $roles,
                'your_role' => $userRole
            ], 403);
        }

        return $next($request);
    }
}