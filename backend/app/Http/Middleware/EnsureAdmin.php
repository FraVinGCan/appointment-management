<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
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

        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin access is required.'], 403);
        }

        return $next($request);
    }
}
