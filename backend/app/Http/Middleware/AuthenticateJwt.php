<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\JwtToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $token = substr($header, 7);
            $payload = JwtToken::parse($token);
            $user = User::find($payload['id'] ?? null);
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $request->attributes->set('auth_user', $user);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        return $next($request);
    }
}

