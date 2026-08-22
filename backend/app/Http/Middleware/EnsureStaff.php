<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        if (! $request->user()->is_staff) {
            return response()->json(['message' => 'Staff access is required.'], 403);
        }

        return $next($request);
    }
}
