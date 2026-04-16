<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function jwt_create(array $payload): string
{
    $secret = (string) envv('JWT_SECRET', 'dev_secret_change_me');
    $ttl = (int) envv('JWT_TTL_SECONDS', 604800);

    return JWT::encode([
        'id' => $payload['id'],
        'role' => $payload['role'],
        'name' => $payload['name'] ?? null,
        'email' => $payload['email'] ?? null,
        'iat' => time(),
        'exp' => time() + $ttl,
    ], $secret, 'HS256');
}

function jwt_parse(string $token): array
{
    $secret = (string) envv('JWT_SECRET', 'dev_secret_change_me');
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));
    return (array) $decoded;
}

function require_auth(): array
{
    $header = request_header('Authorization');
    if (!str_starts_with((string) $header, 'Bearer ')) {
        json_response(['message' => 'Unauthorized'], 401);
    }

    $token = substr((string) $header, 7);
    try {
        $payload = jwt_parse($token);
    } catch (Throwable $e) {
        json_response(['message' => 'Invalid or expired token'], 401);
    }

    $id = (string) ($payload['id'] ?? '');
    if ($id === '') {
        json_response(['message' => 'Invalid or expired token'], 401);
    }

    $user = db_one('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$user) {
        json_response(['message' => 'Unauthorized'], 401);
    }

    return ['payload' => $payload, 'user' => $user];
}

function require_role(array $roles): array
{
    $ctx = require_auth();
    if (!in_array($ctx['user']['role'], $roles, true)) {
        json_response(['message' => 'Forbidden'], 403);
    }
    return $ctx;
}

function current_student(string $userId): ?array
{
    return db_one(
        'SELECT s.*, u.name AS user_name, u.email AS user_email, u.role AS user_role, l.level_name
         FROM students s
         INNER JOIN users u ON u.id = s.user_id
         LEFT JOIN levels l ON l.id = s.level_id
         WHERE s.user_id = :user_id
         LIMIT 1',
        ['user_id' => $userId]
    );
}

function current_tutor(string $userId): ?array
{
    return db_one(
        'SELECT t.*, u.name AS user_name, u.email AS user_email, u.role AS user_role
         FROM tutors t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.user_id = :user_id
         LIMIT 1',
        ['user_id' => $userId]
    );
}

function require_active_subscription(string $userId): void
{
    $student = current_student($userId);
    if (!$student || empty($student['subscription_end'])) {
        json_response(['message' => 'Subscription expired. Please renew.'], 403);
    }

    $isFuture = strtotime((string) $student['subscription_end']) > time();
    $isActive = ($student['subscription_status'] ?? '') === 'active' && $isFuture;
    if (!$isActive) {
        db_exec_sql('UPDATE students SET subscription_status = :status, updated_at = :updated_at WHERE id = :id', [
            'status' => 'expired',
            'updated_at' => now_sql(),
            'id' => $student['id'],
        ]);
        json_response(['message' => 'Subscription expired. Please renew.'], 403);
    }
}

