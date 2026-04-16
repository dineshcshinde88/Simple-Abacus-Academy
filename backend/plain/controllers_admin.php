<?php

function controller_admin_students(): void
{
    $rows = db_all(
        'SELECT
            s.*,
            su.id AS student_user_id,
            su.name AS student_user_name,
            su.email AS student_user_email,
            su.role AS student_user_role,
            l.id AS level_id_ref,
            l.level_name AS level_name_ref,
            l.duration AS level_duration_ref,
            l.description AS level_description_ref,
            t.id AS tutor_id_ref,
            t.user_id AS tutor_user_id_ref,
            tu.name AS tutor_user_name,
            tu.email AS tutor_user_email,
            tu.role AS tutor_user_role
         FROM students s
         INNER JOIN users su ON su.id = s.user_id
         LEFT JOIN levels l ON l.id = s.level_id
         LEFT JOIN tutors t ON t.id = s.tutor_id
         LEFT JOIN users tu ON tu.id = t.user_id
         ORDER BY s.created_at DESC'
    );

    $students = array_map(static function (array $row): array {
        $student = $row;
        $student['user'] = [
            'id' => $row['student_user_id'],
            'name' => $row['student_user_name'],
            'email' => $row['student_user_email'],
            'role' => $row['student_user_role'],
        ];
        $student['level'] = $row['level_id_ref'] ? [
            'id' => $row['level_id_ref'],
            'level_name' => $row['level_name_ref'],
            'duration' => $row['level_duration_ref'],
            'description' => $row['level_description_ref'],
        ] : null;
        $student['tutor'] = $row['tutor_id_ref'] ? [
            'id' => $row['tutor_id_ref'],
            'user_id' => $row['tutor_user_id_ref'],
            'user' => [
                'id' => $row['tutor_user_id_ref'],
                'name' => $row['tutor_user_name'],
                'email' => $row['tutor_user_email'],
                'role' => $row['tutor_user_role'],
            ],
        ] : null;

        unset(
            $student['student_user_id'],
            $student['student_user_name'],
            $student['student_user_email'],
            $student['student_user_role'],
            $student['level_id_ref'],
            $student['level_name_ref'],
            $student['level_duration_ref'],
            $student['level_description_ref'],
            $student['tutor_id_ref'],
            $student['tutor_user_id_ref'],
            $student['tutor_user_name'],
            $student['tutor_user_email'],
            $student['tutor_user_role']
        );

        return $student;
    }, $rows);

    json_response(['students' => $students]);
}

function controller_admin_tutors(): void
{
    $tutors = db_all(
        'SELECT t.*, u.id AS user_id_ref, u.name AS user_name, u.email AS user_email, u.role AS user_role
         FROM tutors t
         INNER JOIN users u ON u.id = t.user_id
         ORDER BY t.created_at DESC'
    );

    $result = [];
    foreach ($tutors as $t) {
        $students = db_all(
            'SELECT s.*, su.name AS student_user_name, su.email AS student_user_email, su.role AS student_user_role
             FROM students s
             INNER JOIN users su ON su.id = s.user_id
             WHERE s.tutor_id = :tutor_id',
            ['tutor_id' => $t['id']]
        );

        $result[] = [
            'id' => $t['id'],
            'user_id' => $t['user_id'],
            'created_at' => $t['created_at'],
            'updated_at' => $t['updated_at'],
            'user' => [
                'id' => $t['user_id_ref'],
                'name' => $t['user_name'],
                'email' => $t['user_email'],
                'role' => $t['user_role'],
            ],
            'students' => array_map(static function (array $s): array {
                $student = $s;
                $student['user'] = [
                    'id' => $s['user_id'],
                    'name' => $s['student_user_name'],
                    'email' => $s['student_user_email'],
                    'role' => $s['student_user_role'],
                ];
                unset($student['student_user_name'], $student['student_user_email'], $student['student_user_role']);
                return $student;
            }, $students),
        ];
    }

    json_response(['tutors' => $result]);
}

function controller_admin_stats(): void
{
    json_response([
        'students' => (int) db_value('SELECT COUNT(*) FROM students'),
        'tutors' => (int) db_value('SELECT COUNT(*) FROM tutors'),
        'activeSubscriptions' => (int) db_value('SELECT COUNT(*) FROM students WHERE subscription_status = :status', ['status' => 'active']),
    ]);
}

function controller_admin_create_plan(array $data): void
{
    $name = trim((string) ($data['name'] ?? ''));
    $durationDays = (int) ($data['durationDays'] ?? 0);
    $price = (float) ($data['price'] ?? 0);

    if ($name === '' || $durationDays <= 0 || $price < 0) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $exists = db_one('SELECT id FROM subscription_plans WHERE name = :name LIMIT 1', ['name' => $name]);
    if ($exists) {
        json_response(['message' => 'Plan already exists'], 409);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO subscription_plans (id, name, duration_days, price, created_at, updated_at)
         VALUES (:id, :name, :duration_days, :price, :created_at, :updated_at)',
        [
            'id' => $id,
            'name' => $name,
            'duration_days' => $durationDays,
            'price' => $price,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    json_response(['plan' => db_one('SELECT * FROM subscription_plans WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_assign_tutor(string $studentId, array $data): void
{
    $tutorId = trim((string) ($data['tutorId'] ?? ''));
    if ($tutorId === '') {
        json_response(['message' => 'tutorId is required'], 422);
    }

    $tutor = db_one('SELECT * FROM tutors WHERE id = :id', ['id' => $tutorId]);
    $student = db_one('SELECT * FROM students WHERE id = :id', ['id' => $studentId]);
    if (!$tutor || !$student) {
        json_response(['message' => 'Tutor or student not found'], 404);
    }

    db_exec_sql('UPDATE students SET tutor_id = :tutor_id, updated_at = :updated_at WHERE id = :id', [
        'tutor_id' => $tutorId,
        'updated_at' => now_sql(),
        'id' => $studentId,
    ]);

    json_response([
        'student' => db_one('SELECT * FROM students WHERE id = :id', ['id' => $studentId]),
        'tutor' => $tutor,
    ]);
}

function controller_admin_assign_subscription(string $studentId, array $data): void
{
    $student = db_one('SELECT * FROM students WHERE id = :id', ['id' => $studentId]);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $plan = null;
    $planId = trim((string) ($data['planId'] ?? ''));
    if ($planId !== '') {
        $plan = db_one('SELECT * FROM subscription_plans WHERE id = :id', ['id' => $planId]);
        if (!$plan) {
            json_response(['message' => 'Plan not found'], 404);
        }
    }

    $days = $plan ? (int) $plan['duration_days'] : (int) ($data['durationDays'] ?? 0);
    if ($days <= 0) {
        json_response(['message' => 'Duration days is required'], 400);
    }

    $startDate = trim((string) ($data['startDate'] ?? ''));
    $startTs = $startDate !== '' ? strtotime($startDate) : time();
    if ($startTs === false) {
        $startTs = time();
    }
    $endTs = $startTs + ($days * 86400);

    $planName = $plan ? (string) $plan['name'] : 'Custom Plan';
    db_exec_sql(
        'UPDATE students
         SET subscription_plan = :plan, subscription_start = :start_date, subscription_end = :end_date,
             subscription_status = :status, updated_at = :updated_at
         WHERE id = :id',
        [
            'plan' => $planName,
            'start_date' => gmdate('Y-m-d H:i:s', $startTs),
            'end_date' => gmdate('Y-m-d H:i:s', $endTs),
            'status' => 'active',
            'updated_at' => now_sql(),
            'id' => $studentId,
        ]
    );

    $updated = db_one('SELECT subscription_plan, subscription_start, subscription_end, subscription_status FROM students WHERE id = :id', ['id' => $studentId]);
    json_response([
        'subscription' => [
            'planName' => $updated['subscription_plan'],
            'startDate' => $updated['subscription_start'],
            'endDate' => $updated['subscription_end'],
            'status' => $updated['subscription_status'],
        ],
    ]);
}

function controller_admin_create_level(array $data): void
{
    $levelName = trim((string) ($data['levelName'] ?? ''));
    $duration = (int) ($data['duration'] ?? 0);
    $description = isset($data['description']) ? trim((string) $data['description']) : null;

    if ($levelName === '' || $duration <= 0) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO levels (id, level_name, duration, description, created_at, updated_at)
         VALUES (:id, :level_name, :duration, :description, :created_at, :updated_at)',
        [
            'id' => $id,
            'level_name' => $levelName,
            'duration' => $duration,
            'description' => $description !== '' ? $description : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    json_response(['level' => db_one('SELECT * FROM levels WHERE id = :id', ['id' => $id])], 201);
}

