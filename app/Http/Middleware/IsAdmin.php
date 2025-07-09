<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_active == 1 && $request->user()->is_verified == 1 && $request->user()->is_super_admin == 1) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
