<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function jwt_create(array $payload): string
{
    $secret = (string) envv('JWT_SECRET', 'dev_secret_change_me');
    $ttl = (int) envv('JWT_TTL_SECONDS', 604800);

    $claims = [
        'id' => $payload['id'],
        'role' => $payload['role'],
        'name' => $payload['name'] ?? null,
        'email' => $payload['email'] ?? null,
        'iat' => time(),
        'exp' => time() + $ttl,
    ];

    if (($payload['role'] ?? '') === 'student' && !empty($payload['session_id'])) {
        $claims['sid'] = (string) $payload['session_id'];
    }

    return JWT::encode($claims, $secret, 'HS256');
}

function jwt_parse(string $token): array
{
    $secret = (string) envv('JWT_SECRET', 'dev_secret_change_me');
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));
    return (array) $decoded;
}

function ensure_student_auth_session_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS student_auth_sessions (
            user_id CHAR(36) PRIMARY KEY,
            session_id CHAR(36) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_student_auth_sessions_session (session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function issue_student_auth_session(string $userId): string
{
    ensure_student_auth_session_schema();
    $sessionId = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO student_auth_sessions (user_id, session_id, created_at, updated_at)
         VALUES (:user_id, :session_id, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE session_id = VALUES(session_id), updated_at = VALUES(updated_at)',
        [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    return $sessionId;
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

    if (function_exists('ensure_user_compatibility_schema')) {
        ensure_user_compatibility_schema();
    }

    $user = db_one('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$user) {
        json_response(['message' => 'Unauthorized'], 401);
    }

    if (($user['role'] ?? '') === 'student') {
        ensure_student_auth_session_schema();
        $activeSessionId = (string) db_value(
            'SELECT session_id FROM student_auth_sessions WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $id]
        );
        $tokenSessionId = (string) ($payload['sid'] ?? '');
        if ($activeSessionId === '' || $tokenSessionId === '' || !hash_equals($activeSessionId, $tokenSessionId)) {
            json_response([
                'message' => 'Your account was logged in on another device.',
                'code' => 'STUDENT_SESSION_REPLACED',
            ], 401);
        }
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
    if (function_exists('ensure_student_registration_schema')) {
        ensure_student_registration_schema();
    }
    if (function_exists('ensure_billing_schema')) {
        ensure_billing_schema();
    }

    $student = db_one(
        'SELECT s.*, u.name AS user_name, u.email AS user_email, u.role AS user_role, l.level_name, l.course_id, c.name AS course_name, c.slug AS course_slug
         FROM students s
         INNER JOIN users u ON u.id = s.user_id
         LEFT JOIN levels l ON l.id = s.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         WHERE s.user_id = :user_id
         LIMIT 1',
        ['user_id' => $userId]
    );

    if (!$student && function_exists('ensure_student_profile_for_user_id')) {
        ensure_student_profile_for_user_id($userId, true);
        $student = db_one(
            'SELECT s.*, u.name AS user_name, u.email AS user_email, u.role AS user_role, l.level_name, l.course_id, c.name AS course_name, c.slug AS course_slug
             FROM students s
             INNER JOIN users u ON u.id = s.user_id
             LEFT JOIN levels l ON l.id = s.level_id
             LEFT JOIN courses c ON c.id = l.course_id
             WHERE s.user_id = :user_id
             LIMIT 1',
            ['user_id' => $userId]
        );
    }

    return $student;
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

    if (function_exists('sync_student_subscription_state') && !empty($student['id'])) {
        sync_student_subscription_state((string) $student['id']);
        $student = current_student($userId);
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
