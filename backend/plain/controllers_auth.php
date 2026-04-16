<?php

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

    $user = db_one('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if (!$user || !password_verify($password, (string) $user['password'])) {
        json_response(['message' => 'Invalid email or password'], 401);
    }

    if ($role !== '' && !in_array($role, ['student', 'tutor', 'admin'], true)) {
        json_response(['message' => 'Invalid role'], 401);
    }
    if ($role !== '' && $user['role'] !== $role) {
        json_response(['message' => 'Invalid role'], 401);
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

