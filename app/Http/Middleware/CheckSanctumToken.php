<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;

class CheckSanctumToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token || !PersonalAccessToken::findToken($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Token is missing or invalid'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}

