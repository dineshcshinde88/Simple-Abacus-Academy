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

function controller_admin_courses_list(): void
{
    $rows = db_all('SELECT * FROM courses ORDER BY name ASC');
    json_response(['courses' => $rows]);
}

function controller_admin_create_course(array $data): void
{
    $name = trim((string) ($data['name'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? ''));
    $description = isset($data['description']) ? trim((string) $data['description']) : null;

    if ($name === '' || $slug === '') {
        json_response(['message' => 'name and slug are required'], 422);
    }

    $existing = db_one('SELECT id FROM courses WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    if ($existing) {
        json_response(['message' => 'Course slug already exists'], 409);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO courses (id, name, slug, description, created_at, updated_at)
         VALUES (:id, :name, :slug, :description, :created_at, :updated_at)',
        [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description !== '' ? $description : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    json_response(['course' => db_one('SELECT * FROM courses WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_update_course(string $courseId, array $data): void
{
    $course = db_one('SELECT * FROM courses WHERE id = :id LIMIT 1', ['id' => $courseId]);
    if (!$course) {
        json_response(['message' => 'Course not found'], 404);
    }

    $name = trim((string) ($data['name'] ?? $course['name']));
    $slug = trim((string) ($data['slug'] ?? $course['slug']));
    $description = isset($data['description']) ? trim((string) $data['description']) : $course['description'];

    if ($name === '' || $slug === '') {
        json_response(['message' => 'name and slug are required'], 422);
    }

    $duplicate = db_one('SELECT id FROM courses WHERE slug = :slug AND id <> :id LIMIT 1', ['slug' => $slug, 'id' => $courseId]);
    if ($duplicate) {
        json_response(['message' => 'Course slug already exists'], 409);
    }

    db_exec_sql(
        'UPDATE courses SET name = :name, slug = :slug, description = :description, updated_at = :updated_at WHERE id = :id',
        ['name' => $name, 'slug' => $slug, 'description' => $description !== '' ? $description : null, 'updated_at' => now_sql(), 'id' => $courseId]
    );

    json_response(['course' => db_one('SELECT * FROM courses WHERE id = :id', ['id' => $courseId])]);
}

function controller_admin_delete_course(string $courseId): void
{
    $course = db_one('SELECT * FROM courses WHERE id = :id LIMIT 1', ['id' => $courseId]);
    if (!$course) {
        json_response(['message' => 'Course not found'], 404);
    }

    db_exec_sql('DELETE FROM levels WHERE course_id = :course_id', ['course_id' => $courseId]);
    db_exec_sql('DELETE FROM courses WHERE id = :id', ['id' => $courseId]);

    json_response(['message' => 'Course deleted']);
}

function controller_admin_update_level(string $levelId, array $data): void
{
    $level = db_one('SELECT * FROM levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }

    $levelName = trim((string) ($data['levelName'] ?? $level['level_name']));
    $duration = (int) ($data['duration'] ?? $level['duration']);
    $description = isset($data['description']) ? trim((string) $data['description']) : $level['description'];
    $courseId = trim((string) ($data['courseId'] ?? $level['course_id']));

    if ($levelName === '' || $duration <= 0 || $courseId === '') {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $course = db_one('SELECT id FROM courses WHERE id = :id LIMIT 1', ['id' => $courseId]);
    if (!$course) {
        json_response(['message' => 'Course not found'], 404);
    }

    db_exec_sql(
        'UPDATE levels SET level_name = :level_name, course_id = :course_id, duration = :duration, description = :description, updated_at = :updated_at WHERE id = :id',
        [
            'level_name' => $levelName,
            'course_id' => $courseId,
            'duration' => $duration,
            'description' => $description !== '' ? $description : null,
            'updated_at' => now_sql(),
            'id' => $levelId,
        ]
    );

    json_response(['level' => db_one('SELECT * FROM levels WHERE id = :id', ['id' => $levelId])]);
}

function controller_admin_delete_level(string $levelId): void
{
    $level = db_one('SELECT * FROM levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }

    db_exec_sql('DELETE FROM worksheets WHERE level_id = :level_id', ['level_id' => $levelId]);
    db_exec_sql('DELETE FROM subscription_plans WHERE level_id = :level_id', ['level_id' => $levelId]);
    db_exec_sql('DELETE FROM levels WHERE id = :id', ['id' => $levelId]);

    json_response(['message' => 'Level deleted']);
}

function controller_admin_worksheets_list(): void
{
    $rows = db_all(
        'SELECT w.*, l.level_name, c.name AS course_name
         FROM worksheets w
         LEFT JOIN levels l ON l.id = w.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         ORDER BY w.created_at DESC'
    );
    json_response(['worksheets' => $rows]);
}

function controller_admin_update_worksheet(string $worksheetId, array $data): void
{
    $worksheet = db_one('SELECT * FROM worksheets WHERE id = :id LIMIT 1', ['id' => $worksheetId]);
    if (!$worksheet) {
        json_response(['message' => 'Worksheet not found'], 404);
    }

    $title = trim((string) ($data['title'] ?? $worksheet['title']));
    $pdfUrl = trim((string) ($data['pdfUrl'] ?? $worksheet['pdf_url']));
    $levelId = trim((string) ($data['levelId'] ?? $worksheet['level_id']));

    if ($title === '' || $levelId === '') {
        json_response(['message' => 'title and levelId are required'], 422);
    }

    $level = db_one('SELECT * FROM levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }

    db_exec_sql(
        'UPDATE worksheets SET title = :title, pdf_url = :pdf_url, level_id = :level_id, updated_at = :updated_at WHERE id = :id',
        ['title' => $title, 'pdf_url' => $pdfUrl, 'level_id' => $levelId, 'updated_at' => now_sql(), 'id' => $worksheetId]
    );

    json_response(['worksheet' => db_one('SELECT * FROM worksheets WHERE id = :id', ['id' => $worksheetId])]);
}

function controller_admin_delete_worksheet(string $worksheetId): void
{
    $worksheet = db_one('SELECT * FROM worksheets WHERE id = :id LIMIT 1', ['id' => $worksheetId]);
    if (!$worksheet) {
        json_response(['message' => 'Worksheet not found'], 404);
    }

    db_exec_sql('DELETE FROM worksheets WHERE id = :id', ['id' => $worksheetId]);
    json_response(['message' => 'Worksheet deleted']);
}

function controller_admin_create_plan(array $data): void
{
    ensure_billing_schema();

    $name = trim((string) ($data['name'] ?? ''));
    $durationDays = (int) ($data['durationDays'] ?? 0);
    $price = (float) ($data['price'] ?? 0);
    $levelId = trim((string) ($data['levelId'] ?? ''));
    $currency = strtoupper(trim((string) ($data['currency'] ?? 'INR')));
    $isActive = array_key_exists('isActive', $data) ? ((bool) $data['isActive']) : true;

    if ($name === '' || $durationDays <= 0 || $price < 0 || $levelId === '') {
        json_response(['message' => 'Invalid request data'], 422);
    }
    if ($currency === '') {
        $currency = 'INR';
    }

    $level = db_one('SELECT id FROM levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
    if (!$level) {
        json_response(['message' => 'Level not found'], 404);
    }

    $exists = db_one('SELECT id FROM subscription_plans WHERE name = :name LIMIT 1', ['name' => $name]);
    if ($exists) {
        json_response(['message' => 'Plan already exists'], 409);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO subscription_plans (id, name, level_id, duration_days, price, currency, is_active, created_at, updated_at)
         VALUES (:id, :name, :level_id, :duration_days, :price, :currency, :is_active, :created_at, :updated_at)',
        [
            'id' => $id,
            'name' => $name,
            'level_id' => $levelId,
            'duration_days' => $durationDays,
            'price' => $price,
            'currency' => $currency,
            'is_active' => $isActive ? 1 : 0,
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
    ensure_billing_schema();

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
    $startDate = gmdate('Y-m-d H:i:s', $startTs);
    $endDate = gmdate('Y-m-d H:i:s', $endTs);
    $now = now_sql();

    $pdo = db_conn();
    $pdo->beginTransaction();
    try {
        db_exec_sql(
            'UPDATE student_subscriptions SET status = :status, updated_at = :updated_at WHERE student_id = :student_id AND status = "active"',
            ['status' => 'expired', 'updated_at' => $now, 'student_id' => $studentId]
        );

        db_exec_sql(
            'INSERT INTO student_subscriptions
             (id, student_id, plan_id, level_id, plan_name, amount, currency, start_date, expiry_date, status, payment_status, notes, created_at, updated_at)
             VALUES
             (:id, :student_id, :plan_id, :level_id, :plan_name, :amount, :currency, :start_date, :expiry_date, :status, :payment_status, :notes, :created_at, :updated_at)',
            [
                'id' => uuid_v4(),
                'student_id' => $studentId,
                'plan_id' => $plan['id'] ?? null,
                'level_id' => $plan['level_id'] ?? ($student['level_id'] ?? null),
                'plan_name' => $planName,
                'amount' => $plan ? (float) ($plan['price'] ?? 0) : 0,
                'currency' => $plan['currency'] ?? 'INR',
                'start_date' => $startDate,
                'expiry_date' => $endDate,
                'status' => 'active',
                'payment_status' => 'paid',
                'notes' => 'Assigned manually by admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        db_exec_sql(
            'UPDATE students
             SET level_id = :level_id, subscription_plan = :plan, subscription_start = :start_date, subscription_end = :end_date,
                 subscription_status = :status, updated_at = :updated_at
             WHERE id = :id',
            [
                'level_id' => $plan['level_id'] ?? ($student['level_id'] ?? null),
                'plan' => $planName,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'updated_at' => $now,
                'id' => $studentId,
            ]
        );

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['message' => 'Failed to assign subscription'], 500);
    }

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
    $courseId = trim((string) ($data['courseId'] ?? ''));

    if ($levelName === '' || $duration <= 0 || $courseId === '') {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $course = db_one('SELECT id FROM courses WHERE id = :id LIMIT 1', ['id' => $courseId]);
    if (!$course) {
        json_response(['message' => 'Course not found'], 404);
    }

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO levels (id, level_name, course_id, duration, description, created_at, updated_at)
         VALUES (:id, :level_name, :course_id, :duration, :description, :created_at, :updated_at)',
        [
            'id' => $id,
            'level_name' => $levelName,
            'course_id' => $courseId,
            'duration' => $duration,
            'description' => $description !== '' ? $description : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    json_response(['level' => db_one('SELECT * FROM levels WHERE id = :id', ['id' => $id])], 201);
}
