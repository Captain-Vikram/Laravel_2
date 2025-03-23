<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ShortenerLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the user IP or ID if authenticated
        $key = $request->user() ? $request->user()->id : $request->ip();
        
        // Allow 10 URL shortening requests per minute
        if (RateLimiter::tooManyAttempts('shorten:'.$key, 10)) {
            return response()->json([
                'message' => 'Too many requests, please try again later.'
            ], 429);
        }
        
        RateLimiter::hit('shorten:'.$key);
        
        return $next($request);
    }
}
