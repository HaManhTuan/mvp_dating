<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if (!$request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        try {
            // Attempt to authenticate
            if (empty($guards)) {
                $guards = [null];
            }

            foreach ($guards as $guard) {
                if (auth()->guard($guard)->check()) {
                    auth()->shouldUse($guard);
                    return $next($request);
                }
            }

            // If none of the guards authenticates, throw an exception
            throw new AuthenticationException('Unauthenticated.', $guards);
        } catch (AuthenticationException $e) {
            // Custom JSON response for authentication failure
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Authentication required.',
                'errors' => $e->getMessage()
            ], 401);
        }
    }
}
