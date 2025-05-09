<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when not authenticated.
     */
    // protected function redirectTo($request): ?string
    // {
    //     if ($request->is('api/*')) {
    //         return null; // Prevent redirect for API routes
    //     }

    //     return route('login');
    // }      
    protected function redirectTo($request): ?string
    {
        return null; // Disable ALL redirects
    }

    
    /**
     * Override unauthenticated response
     */
    protected function unauthenticated($request, array $guards)
    {
        // For API routes
        if ($request->is('api/*')) {
            abort(response()->json([
                'message' => 'Authentication required',
                'error' => 'Unauthenticated'
            ], 401));
        }

        // For web routes (if any)
        parent::unauthenticated($request, $guards);
    }
}
