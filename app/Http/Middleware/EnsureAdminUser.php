<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('admin.login'))
                ->with('error', 'Please log in with administrator credentials to proceed.');
        }

        if (! $request->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. Administrator privileges required.'], 403);
            }
            abort(403, 'Access denied. You do not have administrator privileges to view this area.');
        }

        return $next($request);
    }
}
