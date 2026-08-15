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

function auth_table_has_column(string $table, string $column): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column',
        ['table' => $table, 'column' => $column]
    ) > 0;
}

function auth_table_type(string $table): string
{
    return (string) db_value(
        'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1',
        ['table' => $table]
    );
}

function auth_table_exists(string $table): bool
{
    return auth_table_type($table) !== '';
}

function auth_ensure_column(string $table, string $column, string $definition): void
{
    if (auth_table_exists($table) && !auth_table_has_column($table, $column)) {
        db_exec_sql("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function auth_writable_table(array $candidates): string
{
    foreach ($candidates as $table) {
        $row = db_one(
            'SELECT TABLE_NAME, TABLE_TYPE
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER(:table)
             ORDER BY CASE WHEN BINARY TABLE_NAME = BINARY :exact_table THEN 0 ELSE 1 END
             LIMIT 1',
            ['table' => $table, 'exact_table' => $table]
        );
        if ($row && (string) ($row['TABLE_TYPE'] ?? '') === 'BASE TABLE') {
            return (string) $row['TABLE_NAME'];
        }
    }

    return $candidates[0];
}

function auth_table_column(string $table, string $snakeColumn, string $camelColumn): string
{
    if (auth_table_has_column($table, $snakeColumn)) {
        return $snakeColumn;
    }
    if (auth_table_has_column($table, $camelColumn)) {
        return $camelColumn;
    }
    return $snakeColumn;
}
function ensure_user_compatibility_schema(): void
{
    if (auth_table_type('users') === 'BASE TABLE') {
        return;
    }

    if (auth_table_exists('User')) {
        db_exec_sql(
            'CREATE OR REPLACE VIEW users AS
             SELECT
               `User`.id AS id,
               `User`.name AS name,
               `User`.email AS email,
               `User`.password AS password,
               `User`.role AS role,
               `User`.createdAt AS created_at,
               `User`.updatedAt AS updated_at
             FROM `User`'
        );
    }
}

function ensure_student_registration_schema(): void
{
    ensure_user_compatibility_schema();
    if (auth_table_type('students') === 'BASE TABLE') {
        auth_ensure_column('students', 'course', "VARCHAR(120) NOT NULL DEFAULT '' AFTER user_id");
        auth_ensure_column('students', 'phone_country', "VARCHAR(10) NOT NULL DEFAULT '+91' AFTER course");
        auth_ensure_column('students', 'phone', "VARCHAR(40) NOT NULL DEFAULT '' AFTER phone_country");
        auth_ensure_column('students', 'gender', "VARCHAR(30) NOT NULL DEFAULT '' AFTER phone");
        auth_ensure_column('students', 'mother_tongue', "VARCHAR(120) NOT NULL DEFAULT '' AFTER gender");
        auth_ensure_column('students', 'whatsapp_number', "VARCHAR(40) NOT NULL DEFAULT '' AFTER mother_tongue");
        auth_ensure_column('students', 'dob', 'DATE NULL AFTER mother_tongue');
        auth_ensure_column('students', 'fees_status', 'VARCHAR(20) NULL AFTER dob');
        return;
    }

    if (auth_table_type('students') === 'VIEW' && auth_table_exists('student')) {
        auth_ensure_column('student', 'course', "VARCHAR(120) NOT NULL DEFAULT '' AFTER userId");
        auth_ensure_column('student', 'phoneCountry', "VARCHAR(10) NOT NULL DEFAULT '+91' AFTER course");
        auth_ensure_column('student', 'phone', "VARCHAR(40) NOT NULL DEFAULT '' AFTER phoneCountry");
        auth_ensure_column('student', 'gender', "VARCHAR(30) NOT NULL DEFAULT '' AFTER phone");
        auth_ensure_column('student', 'motherTongue', "VARCHAR(120) NOT NULL DEFAULT '' AFTER gender");
        auth_ensure_column('student', 'whatsappNumber', "VARCHAR(40) NOT NULL DEFAULT '' AFTER motherTongue");
          auth_ensure_column('student', 'dob', 'DATE NULL AFTER motherTongue');
          auth_ensure_column('student', 'feesStatus', 'VARCHAR(20) NULL AFTER dob');
        db_exec_sql(
            'CREATE OR REPLACE VIEW students AS
             SELECT
               student.id AS id,
               student.userId AS user_id,
               student.course AS course,
               student.phoneCountry AS phone_country,
               student.phone AS phone,
               student.gender AS gender,
               student.motherTongue AS mother_tongue,
               student.whatsappNumber AS whatsapp_number,
               student.dob AS dob,
               student.tutorId AS tutor_id,
               student.levelId AS level_id,
               student.batches AS batches,
               student.subscriptionPlan AS subscription_plan,
               student.subscriptionStart AS subscription_start,
               student.subscriptionEnd AS subscription_end,
               student.subscriptionStatus AS subscription_status,
               student.createdAt AS created_at,
               student.updatedAt AS updated_at
             FROM student'
        );
    }

    if (!auth_table_exists('students') && auth_table_exists('Student')) {
        auth_ensure_column('Student', 'course', "VARCHAR(120) NOT NULL DEFAULT '' AFTER userId");
        auth_ensure_column('Student', 'phoneCountry', "VARCHAR(10) NOT NULL DEFAULT '+91' AFTER course");
        auth_ensure_column('Student', 'phone', "VARCHAR(40) NOT NULL DEFAULT '' AFTER phoneCountry");
        auth_ensure_column('Student', 'gender', "VARCHAR(30) NOT NULL DEFAULT '' AFTER phone");
        auth_ensure_column('Student', 'motherTongue', "VARCHAR(120) NOT NULL DEFAULT '' AFTER gender");
        auth_ensure_column('Student', 'whatsappNumber', "VARCHAR(40) NOT NULL DEFAULT '' AFTER motherTongue");
          auth_ensure_column('Student', 'dob', 'DATE NULL AFTER motherTongue');
          auth_ensure_column('Student', 'feesStatus', 'VARCHAR(20) NULL AFTER dob');
        db_exec_sql(
            'CREATE OR REPLACE VIEW students AS
             SELECT
               Student.id AS id,
               Student.userId AS user_id,
               Student.course AS course,
               Student.phoneCountry AS phone_country,
               Student.phone AS phone,
               Student.gender AS gender,
               Student.motherTongue AS mother_tongue,
               Student.whatsappNumber AS whatsapp_number,
               Student.dob AS dob,
               Student.tutorId AS tutor_id,
               Student.levelId AS level_id,
               Student.batches AS batches,
               Student.subscriptionPlan AS subscription_plan,
               Student.subscriptionStart AS subscription_start,
               Student.subscriptionEnd AS subscription_end,
               Student.subscriptionStatus AS subscription_status,
               Student.createdAt AS created_at,
               Student.updatedAt AS updated_at
             FROM Student'
        );
    }
}

function ensure_student_profile_for_user_id(string $userId, bool $syncLegacySubscriptions = true): ?array
{
    $userId = trim($userId);
    if ($userId === '') {
        return null;
    }

    ensure_student_registration_schema();

    $user = db_one('SELECT id, role FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
    if (!$user || (string) ($user['role'] ?? '') !== 'student') {
        return null;
    }

    $existing = db_one('SELECT * FROM students WHERE user_id = :user_id LIMIT 1', ['user_id' => $userId]);
    if ($existing) {
        return $existing;
    }

    $studentsWriteTable = auth_writable_table(['students', 'student', 'Student']);
    $studentUserColumn = auth_table_column($studentsWriteTable, 'user_id', 'userId');
    $studentPhoneCountryColumn = auth_table_column($studentsWriteTable, 'phone_country', 'phoneCountry');
    $studentMotherTongueColumn = auth_table_column($studentsWriteTable, 'mother_tongue', 'motherTongue');
    $studentCreatedColumn = auth_table_column($studentsWriteTable, 'created_at', 'createdAt');
    $studentUpdatedColumn = auth_table_column($studentsWriteTable, 'updated_at', 'updatedAt');

    $studentId = uuid_v4();
    $now = now_sql();

    db_exec_sql(
        "INSERT INTO {$studentsWriteTable} (id, {$studentUserColumn}, course, {$studentPhoneCountryColumn}, phone, gender, {$studentMotherTongueColumn}, dob, {$studentCreatedColumn}, {$studentUpdatedColumn})
         VALUES (:id, :user_id, :course, :phone_country, :phone, :gender, :mother_tongue, :dob, :created_at, :updated_at)",
        [
            'id' => $studentId,
            'user_id' => $userId,
            'course' => '',
            'phone_country' => '+91',
            'phone' => '',
            'gender' => '',
            'mother_tongue' => '',
            'dob' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    if ($syncLegacySubscriptions) {
        try {
            db_exec_sql(
                'UPDATE student_subscriptions SET student_id = :student_id, updated_at = :updated_at WHERE student_id = :user_id',
                ['student_id' => $studentId, 'updated_at' => $now, 'user_id' => $userId]
            );
        } catch (Throwable $e) {
            error_log('[StudentProfileRepair] unable to relink legacy subscriptions for user=' . $userId . ': ' . $e->getMessage());
        }
    }

    if (function_exists('sync_student_subscription_state')) {
        try {
            sync_student_subscription_state($studentId);
        } catch (Throwable $e) {
            error_log('[StudentProfileRepair] unable to sync subscription state for student=' . $studentId . ': ' . $e->getMessage());
        }
    }

    return db_one('SELECT * FROM students WHERE id = :id LIMIT 1', ['id' => $studentId]);
}
function controller_auth_register(array $data): void
{
    ensure_user_compatibility_schema();
    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    $role = (string) ($data['role'] ?? '');
    $course = trim((string) ($data['course'] ?? ''));
    $phoneCountry = trim((string) ($data['phoneCountry'] ?? '+91'));
    $phone = trim((string) ($data['phone'] ?? ''));
    $gender = trim((string) ($data['gender'] ?? ''));
    $motherTongue = trim((string) ($data['motherTongue'] ?? ''));
    $dob = trim((string) ($data['dob'] ?? ''));
    $dobValue = $dob !== '' ? $dob : null;

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || !in_array($role, ['student', 'tutor'], true)) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $exists = db_one('SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($exists) {
        json_response(['message' => 'Email already registered'], 409);
    }

    if ($role === 'student') {
        ensure_student_registration_schema();
        if (function_exists('ensure_student_profile_details_schema')) {
            ensure_student_profile_details_schema();
        }
    }

    $pdo = db_conn();
    $userId = uuid_v4();
    $now = now_sql();
    $usersWriteTable = auth_writable_table(['users', 'user', 'User']);
    $studentsWriteTable = auth_writable_table(['students', 'student', 'Student']);
    $tutorsWriteTable = auth_writable_table(['tutors', 'tutor', 'Tutor']);
    $userCreatedColumn = auth_table_column($usersWriteTable, 'created_at', 'createdAt');
    $userUpdatedColumn = auth_table_column($usersWriteTable, 'updated_at', 'updatedAt');
    $studentUserColumn = auth_table_column($studentsWriteTable, 'user_id', 'userId');
    $studentPhoneCountryColumn = auth_table_column($studentsWriteTable, 'phone_country', 'phoneCountry');
    $studentMotherTongueColumn = auth_table_column($studentsWriteTable, 'mother_tongue', 'motherTongue');
    $studentCreatedColumn = auth_table_column($studentsWriteTable, 'created_at', 'createdAt');
    $studentUpdatedColumn = auth_table_column($studentsWriteTable, 'updated_at', 'updatedAt');
    $tutorUserColumn = auth_table_column($tutorsWriteTable, 'user_id', 'userId');
    $tutorCreatedColumn = auth_table_column($tutorsWriteTable, 'created_at', 'createdAt');
    $tutorUpdatedColumn = auth_table_column($tutorsWriteTable, 'updated_at', 'updatedAt');

    $pdo->beginTransaction();
    try {
        db_exec_sql(
            "INSERT INTO {$usersWriteTable} (id, name, email, password, role, {$userCreatedColumn}, {$userUpdatedColumn})
             VALUES (:id, :name, :email, :password, :role, :created_at, :updated_at)",
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
            $studentId = uuid_v4();
            db_exec_sql(
                "INSERT INTO {$studentsWriteTable} (id, {$studentUserColumn}, course, {$studentPhoneCountryColumn}, phone, gender, {$studentMotherTongueColumn}, dob, {$studentCreatedColumn}, {$studentUpdatedColumn})
                 VALUES (:id, :user_id, :course, :phone_country, :phone, :gender, :mother_tongue, :dob, :created_at, :updated_at)",
                [
                    'id' => $studentId,
                    'user_id' => $userId,
                    'course' => $course,
                    'phone_country' => $phoneCountry !== '' ? $phoneCountry : '+91',
                    'phone' => $phone,
                    'gender' => $gender,
                    'mother_tongue' => $motherTongue,
                    'dob' => $dobValue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            if (function_exists('ensure_student_profile_details_schema')) {
                db_exec_sql(
                    'INSERT INTO student_profile_details
                        (user_id, student_id, phone_country, phone, gender, dob, updated_at)
                     VALUES (:user_id, :student_id, :phone_country, :phone, :gender, :dob, :updated_at)
                     ON DUPLICATE KEY UPDATE
                        student_id = VALUES(student_id), phone_country = VALUES(phone_country),
                        phone = VALUES(phone), gender = VALUES(gender), dob = VALUES(dob), updated_at = VALUES(updated_at)',
                    [
                        'user_id' => $userId,
                        'student_id' => $studentId,
                        'phone_country' => $phoneCountry !== '' ? $phoneCountry : '+91',
                        'phone' => preg_replace('/\D+/', '', $phone) ?? '',
                        'gender' => strtolower($gender),
                        'dob' => $dobValue,
                        'updated_at' => $now,
                    ]
                );
            }
        } else {
            db_exec_sql(
                "INSERT INTO {$tutorsWriteTable} (id, {$tutorUserColumn}, {$tutorCreatedColumn}, {$tutorUpdatedColumn}) VALUES (:id, :user_id, :created_at, :updated_at)",
                ['id' => uuid_v4(), 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Student/tutor registration failed: ' . $e->getMessage());
        json_response(['message' => 'Failed to register user'], 500);
    }

    $user = db_one('SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = :id', ['id' => $userId]);
    if (($user['role'] ?? '') === 'student') {
        $user['session_id'] = issue_student_auth_session((string) $user['id']);
    } elseif (($user['role'] ?? '') === 'tutor') {
        $user['session_id'] = issue_instructor_auth_session((string) $user['id']);
    }
    json_response([
        'token' => jwt_create($user),
        'role' => $user['role'],
        'name' => $user['name'],
        'user' => $user,
    ], 201);
}

function controller_auth_login(array $data): void
{
    ensure_user_compatibility_schema();
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
    if ($safe['role'] === 'student') {
        $safe['session_id'] = issue_student_auth_session((string) $safe['id']);
    } elseif ($safe['role'] === 'tutor') {
        $safe['session_id'] = issue_instructor_auth_session((string) $safe['id']);
    }

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

function controller_auth_change_password(array $ctx, array $data): void
{
    ensure_user_compatibility_schema();
    $currentPassword = (string) ($data['currentPassword'] ?? '');
    $newPassword = (string) ($data['newPassword'] ?? '');
    $confirmPassword = (string) ($data['confirmPassword'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        json_response(['message' => 'All password fields are required'], 422);
    }
    if (strlen($newPassword) < 6) {
        json_response(['message' => 'New password must be at least 6 characters'], 422);
    }
    if ($newPassword !== $confirmPassword) {
        json_response(['message' => 'New password and confirm password do not match'], 422);
    }

    $user = db_one('SELECT id, password FROM users WHERE id = :id LIMIT 1', ['id' => $ctx['user']['id']]);
    if (!$user || !password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
        json_response(['message' => 'Current password is incorrect'], 401);
    }

    db_exec_sql(
        'UPDATE users SET password = :password, updated_at = :updated_at WHERE id = :id',
        [
            'password' => password_hash($newPassword, PASSWORD_BCRYPT),
            'updated_at' => now_sql(),
            'id' => $ctx['user']['id'],
        ]
    );

    json_response(['message' => 'Password changed successfully']);
}

function controller_auth_forgot_password(array $data): void
{
    ensure_user_compatibility_schema();
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
    ensure_user_compatibility_schema();
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
