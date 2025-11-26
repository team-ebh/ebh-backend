<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBroadcasting
{
    /**
     * Handle an incoming request for broadcasting authentication
     *
     * Attempts to authenticate using multiple guards for private channels
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to authenticate with each guard
        $guards = ['customer', 'rider'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Set the authenticated user for broadcasting
                $request->setUserResolver(fn () => Auth::guard($guard)->user());

                return $next($request);
            }
        }

        // If no guard authenticated, return next anyway
        // Laravel broadcasting will handle unauthorized responses
        return $next($request);
    }
}
