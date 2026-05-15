<?php

function ensure_password_reset_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS password_resets (
            id CHAR(36) PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT \'student\',
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_password_resets_email (email),
            INDEX idx_password_resets_token (token_hash),
            INDEX idx_password_resets_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function auth_send_html_mail(string $to, string $subject, string $html, string $text): void
{
    if (function_exists('instructor_send_html_mail')) {
        instructor_send_html_mail($to, $subject, $html, $text);
        return;
    }

    $fromRaw = (string) envv('EMAIL_FROM', (string) envv('MAIL_FROM_ADDRESS', 'Simple Abacus <no-reply@simpleabacus.com>'));
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromRaw,
    ];

    @mail($to, $subject, $html, implode("\r\n", $headers));
}

function auth_frontend_url(): string
{
    $configured = trim((string) envv('FRONTEND_URL', (string) envv('SITE_URL', '')));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $origin = request_header('Origin');
    return rtrim($origin ?: get_base_url(), '/');
}

function controller_auth_register(array $data): void
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    $role = (string) ($data['role'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || !in_array($role, ['student', 'tutor'], true)) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $exists = db_one('SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($exists) {
        json_response(['message' => 'Email already registered'], 409);
    }

    $pdo = db_conn();
    $userId = uuid_v4();
    $now = now_sql();

    $pdo->beginTransaction();
    try {
        db_exec_sql(
            'INSERT INTO users (id, name, email, password, role, created_at, updated_at)
             VALUES (:id, :name, :email, :password, :role, :created_at, :updated_at)',
            [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if ($role === 'student') {
            db_exec_sql(
                'INSERT INTO students (id, user_id, created_at, updated_at) VALUES (:id, :user_id, :created_at, :updated_at)',
                ['id' => uuid_v4(), 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]
            );
        } else {
            db_exec_sql(
                'INSERT INTO tutors (id, user_id, created_at, updated_at) VALUES (:id, :user_id, :created_at, :updated_at)',
                ['id' => uuid_v4(), 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['message' => 'Failed to register user'], 500);
    }

    $user = db_one('SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = :id', ['id' => $userId]);
    json_response([
        'token' => jwt_create($user),
        'role' => $user['role'],
        'name' => $user['name'],
        'user' => $user,
    ], 201);
}

function controller_auth_login(array $data): void
{
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    $role = isset($data['role']) ? (string) $data['role'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        json_response(['message' => 'Invalid email or password'], 401);
    }

    if ($role !== '' && !in_array($role, ['student', 'tutor', 'admin'], true)) {
        json_response(['message' => 'Invalid role'], 401);
    }

    $user = db_one('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);

    if ($role === 'tutor' && function_exists('ensure_instructor_auth_schema')) {
        ensure_instructor_auth_schema();
        $instructor = db_one('SELECT * FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
        if ($instructor) {
            $status = (string) ($instructor['status'] ?? 'pending');
            if ((int) ($instructor['is_verified'] ?? 0) === 1 && $status === 'pending') {
                $status = 'approved';
            }
            if (!password_verify($password, (string) ($instructor['password'] ?? ''))) {
                json_response(['message' => 'Invalid email or password'], 401);
            }
            if ($status === 'pending') {
                json_response(['message' => 'Your account is waiting for admin approval.'], 403);
            }
            if ($status === 'rejected') {
                json_response(['message' => 'Your registration was rejected. Contact admin.'], 403);
            }
            if ($status !== 'approved') {
                json_response(['message' => 'Your account is waiting for admin approval.'], 403);
            }

            if (function_exists('instructor_ensure_approved_user')) {
                instructor_ensure_approved_user($instructor);
            }
            $user = db_one('SELECT * FROM users WHERE email = :email AND role = \'tutor\' LIMIT 1', ['email' => $email]);
        }
    }

    if (!$user || !password_verify($password, (string) $user['password'])) {
        json_response(['message' => 'Invalid email or password'], 401);
    }

    if ($role !== '' && $user['role'] !== $role) {
        json_response(['message' => 'Invalid role'], 401);
    }
    if ($user['role'] === 'tutor' && function_exists('ensure_instructor_auth_schema')) {
        ensure_instructor_auth_schema();
        $instructor = db_one('SELECT status, is_verified, password FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
        if ($instructor) {
            $status = (string) ($instructor['status'] ?? 'pending');
            if ((int) ($instructor['is_verified'] ?? 0) === 1 && $status === 'pending') {
                $status = 'approved';
            }
            if ($status === 'pending') {
                json_response(['message' => 'Your account is waiting for admin approval.'], 403);
            }
            if ($status === 'rejected') {
                json_response(['message' => 'Your registration was rejected. Contact admin.'], 403);
            }
            if ($status !== 'approved') {
                json_response(['message' => 'Your account is waiting for admin approval.'], 403);
            }
        }
    }

    $safe = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'created_at' => $user['created_at'] ?? null,
        'updated_at' => $user['updated_at'] ?? null,
    ];

    json_response([
        'token' => jwt_create($safe),
        'role' => $safe['role'],
        'name' => $safe['name'],
        'user' => $safe,
    ]);
}

function controller_auth_me(array $ctx): void
{
    json_response([
        'user' => [
            'id' => $ctx['user']['id'],
            'name' => $ctx['user']['name'],
            'email' => $ctx['user']['email'],
            'role' => $ctx['user']['role'],
        ],
    ]);
}

function controller_auth_forgot_password(array $data): void
{
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'Please enter a valid email address'], 422);
    }

    ensure_password_reset_schema();

    $user = db_one('SELECT id, name, email, role FROM users WHERE email = :email AND role = \'student\' LIMIT 1', ['email' => $email]);
    $message = 'If a student account exists for this email, a password reset link has been sent.';

    if (!$user) {
        json_response(['message' => $message]);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = gmdate('Y-m-d H:i:s', time() + 3600);
    $now = now_sql();

    db_exec_sql(
        'UPDATE password_resets SET used_at = :used_at WHERE email = :email AND role = \'student\' AND used_at IS NULL',
        ['used_at' => $now, 'email' => $email]
    );
    db_exec_sql(
        'INSERT INTO password_resets (id, email, token_hash, role, expires_at, created_at)
         VALUES (:id, :email, :token_hash, \'student\', :expires_at, :created_at)',
        [
            'id' => uuid_v4(),
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => $now,
        ]
    );

    $resetUrl = auth_frontend_url() . '/student-reset-password?email=' . rawurlencode($email) . '&token=' . rawurlencode($token);
    $safeName = htmlspecialchars((string) ($user['name'] ?? 'Student'), ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $subject = 'Reset your Simple Abacus password';
    $html = '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6">'
        . '<h2 style="color:#4b1e83">Hello ' . $safeName . ',</h2>'
        . '<p>We received a request to reset your Simple Abacus student password.</p>'
        . '<p><a href="' . $safeUrl . '" style="display:inline-block;background:#4b1e83;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px">Reset Password</a></p>'
        . '<p>This link expires in 1 hour. If you did not request this, you can ignore this email.</p>'
        . '<p style="font-size:13px;color:#6b7280">If the button does not work, copy this link: ' . $safeUrl . '</p>'
        . '</div>';
    $text = "Hello {$user['name']},\n\nReset your Simple Abacus password using this link:\n{$resetUrl}\n\nThis link expires in 1 hour.";

    auth_send_html_mail($email, $subject, $html, $text);

    json_response(['message' => $message]);
}

function controller_auth_reset_password(array $data): void
{
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $token = (string) ($data['token'] ?? '');
    $password = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirmPassword'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $token === '' || strlen($password) < 6 || $password !== $confirm) {
        json_response(['message' => 'Please enter matching passwords with at least 6 characters'], 422);
    }

    ensure_password_reset_schema();

    $tokenHash = hash('sha256', $token);
    $reset = db_one(
        'SELECT id FROM password_resets
         WHERE email = :email AND token_hash = :token_hash AND role = \'student\' AND used_at IS NULL AND expires_at > :now
         ORDER BY created_at DESC LIMIT 1',
        ['email' => $email, 'token_hash' => $tokenHash, 'now' => now_sql()]
    );

    if (!$reset) {
        json_response(['message' => 'This reset link is invalid or expired'], 400);
    }

    $user = db_one('SELECT id FROM users WHERE email = :email AND role = \'student\' LIMIT 1', ['email' => $email]);
    if (!$user) {
        json_response(['message' => 'This reset link is invalid or expired'], 400);
    }

    $now = now_sql();
    db_exec_sql(
        'UPDATE users SET password = :password, updated_at = :updated_at WHERE id = :id',
        ['password' => password_hash($password, PASSWORD_BCRYPT), 'updated_at' => $now, 'id' => $user['id']]
    );
    db_exec_sql(
        'UPDATE password_resets SET used_at = :used_at WHERE id = :id',
        ['used_at' => $now, 'id' => $reset['id']]
    );

    json_response(['message' => 'Password reset successfully. You can now login.']);
}
