<?php

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtToken
{
    public static function create(array $payload): string
    {
        $secret = env('JWT_SECRET', 'dev_secret_change_me');
        $ttl = (int) env('JWT_TTL_SECONDS', 60 * 60 * 24 * 7);

        return JWT::encode([
            'id' => $payload['id'],
            'role' => $payload['role'],
            'name' => $payload['name'] ?? null,
            'email' => $payload['email'] ?? null,
            'iat' => time(),
            'exp' => time() + $ttl,
        ], $secret, 'HS256');
    }

    public static function parse(string $token): array
    {
        $secret = env('JWT_SECRET', 'dev_secret_change_me');
        return (array) JWT::decode($token, new Key($secret, 'HS256'));
    }
}

