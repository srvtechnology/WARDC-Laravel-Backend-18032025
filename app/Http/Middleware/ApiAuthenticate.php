<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'Unauthorized. Please log in first.'], 401);
        }

        return $next($request);
    }
}
