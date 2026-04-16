<?php

function controller_tutor_profile(array $ctx): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    json_response([
        'tutor' => [
            'id' => $tutor['id'],
            'user_id' => $tutor['user_id'],
            'created_at' => $tutor['created_at'],
            'updated_at' => $tutor['updated_at'],
            'user' => [
                'id' => $tutor['user_id'],
                'name' => $tutor['user_name'],
                'email' => $tutor['user_email'],
                'role' => $tutor['user_role'],
            ],
        ],
    ]);
}

function controller_tutor_students(array $ctx): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $rows = db_all(
        'SELECT s.*, u.id AS user_id_ref, u.name AS user_name, u.email AS user_email, u.role AS user_role
         FROM students s
         INNER JOIN users u ON u.id = s.user_id
         WHERE s.tutor_id = :tutor_id
         ORDER BY s.created_at DESC',
        ['tutor_id' => $tutor['id']]
    );

    $students = array_map(static function (array $row): array {
        $student = $row;
        $student['user'] = [
            'id' => $row['user_id_ref'],
            'name' => $row['user_name'],
            'email' => $row['user_email'],
            'role' => $row['user_role'],
        ];
        unset($student['user_id_ref'], $student['user_name'], $student['user_email'], $student['user_role']);
        return $student;
    }, $rows);

    json_response(['students' => $students]);
}

function controller_tutor_add_student(array $ctx, array $data): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $exists = db_one('SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($exists) {
        json_response(['message' => 'Email already registered'], 409);
    }

    $pdo = db_conn();
    $userId = uuid_v4();
    $studentId = uuid_v4();
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
                'role' => 'student',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        db_exec_sql(
            'INSERT INTO students (id, user_id, tutor_id, created_at, updated_at)
             VALUES (:id, :user_id, :tutor_id, :created_at, :updated_at)',
            [
                'id' => $studentId,
                'user_id' => $userId,
                'tutor_id' => $tutor['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['message' => 'Failed to add student'], 500);
    }

    $student = db_one('SELECT * FROM students WHERE id = :id', ['id' => $studentId]);
    json_response(['student' => $student], 201);
}

function controller_tutor_assign_level(array $ctx, string $studentId, array $data): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $levelId = trim((string) ($data['levelId'] ?? ''));
    if ($levelId === '') {
        json_response(['message' => 'levelId is required'], 422);
    }

    $level = db_one('SELECT id FROM levels WHERE id = :id', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }

    $student = db_one('SELECT id FROM students WHERE id = :id', ['id' => $studentId]);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    db_exec_sql('UPDATE students SET level_id = :level_id, updated_at = :updated_at WHERE id = :id', [
        'level_id' => $levelId,
        'updated_at' => now_sql(),
        'id' => $studentId,
    ]);

    $updated = db_one('SELECT * FROM students WHERE id = :id', ['id' => $studentId]);
    json_response(['student' => $updated]);
}

function controller_tutor_upload_video(array $ctx, array $data): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $title = trim((string) ($data['title'] ?? ''));
    $levelId = trim((string) ($data['levelId'] ?? ''));
    $url = trim((string) ($data['url'] ?? ''));
    if ($title === '' || $levelId === '') {
        json_response(['message' => 'title and levelId are required'], 422);
    }

    $uploaded = handle_upload_file('file');
    $finalUrl = $url !== '' ? $url : $uploaded;
    if ($finalUrl === '') {
        json_response(['message' => 'Video URL or file is required'], 400);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO videos (id, title, url, level_id, uploaded_by, created_at, updated_at)
         VALUES (:id, :title, :url, :level_id, :uploaded_by, :created_at, :updated_at)',
        [
            'id' => $id,
            'title' => $title,
            'url' => $finalUrl,
            'level_id' => $levelId,
            'uploaded_by' => $tutor['id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $video = db_one('SELECT * FROM videos WHERE id = :id', ['id' => $id]);
    json_response(['video' => $video], 201);
}

function controller_tutor_upload_worksheet(array $ctx, array $data): void
{
    $tutor = current_tutor($ctx['user']['id']);
    if (!$tutor) {
        json_response(['message' => 'Tutor not found'], 404);
    }

    $title = trim((string) ($data['title'] ?? ''));
    $levelId = trim((string) ($data['levelId'] ?? ''));
    $pdfUrl = trim((string) ($data['pdfUrl'] ?? ''));
    if ($title === '' || $levelId === '') {
        json_response(['message' => 'title and levelId are required'], 422);
    }

    $uploaded = handle_upload_file('file');
    $finalUrl = $pdfUrl !== '' ? $pdfUrl : $uploaded;
    if ($finalUrl === '') {
        json_response(['message' => 'Worksheet PDF URL or file is required'], 400);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO worksheets (id, title, pdf_url, level_id, created_by, created_at, updated_at)
         VALUES (:id, :title, :pdf_url, :level_id, :created_by, :created_at, :updated_at)',
        [
            'id' => $id,
            'title' => $title,
            'pdf_url' => $finalUrl,
            'level_id' => $levelId,
            'created_by' => $tutor['id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $worksheet = db_one('SELECT * FROM worksheets WHERE id = :id', ['id' => $id]);
    json_response(['worksheet' => $worksheet], 201);
}

