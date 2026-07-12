<?php

function billing_table_has_column(string $table, string $column): bool
{
    $row = db_one(
        'SELECT COUNT(*) AS c
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name',
        ['table_name' => $table, 'column_name' => $column]
    );
    return ((int) ($row['c'] ?? 0)) > 0;
}

function ensure_billing_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS payment_gateway_configs (
            id CHAR(36) PRIMARY KEY,
            provider VARCHAR(50) NOT NULL UNIQUE,
            key_id VARCHAR(255) NOT NULL,
            secret_enc TEXT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            created_by CHAR(36) NULL,
            updated_by CHAR(36) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS payment_attempts (
            id CHAR(36) PRIMARY KEY,
            student_id CHAR(36) NOT NULL,
            plan_id CHAR(36) NULL,
            provider VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            currency VARCHAR(10) NOT NULL DEFAULT "INR",
            status VARCHAR(20) NOT NULL DEFAULT "created",
            provider_order_id VARCHAR(120) NULL,
            provider_payment_id VARCHAR(120) NULL,
            provider_signature VARCHAR(255) NULL,
            paid_at DATETIME NULL,
            metadata_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_payment_attempts_student (student_id),
            INDEX idx_payment_attempts_provider_order (provider_order_id),
            INDEX idx_payment_attempts_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    if (!billing_table_has_column('payment_attempts', 'allocation_status')) {
        db_exec_sql('ALTER TABLE payment_attempts ADD COLUMN allocation_status VARCHAR(30) NOT NULL DEFAULT "pending" AFTER status');
    }
    if (!billing_table_has_column('payment_attempts', 'allocation_error')) {
        db_exec_sql('ALTER TABLE payment_attempts ADD COLUMN allocation_error TEXT NULL AFTER allocation_status');
    }

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS student_subscriptions (
            id CHAR(36) PRIMARY KEY,
            student_id CHAR(36) NOT NULL,
            plan_id CHAR(36) NULL,
            level_id CHAR(36) NULL,
            plan_name VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            currency VARCHAR(10) NOT NULL DEFAULT "INR",
            start_date DATETIME NOT NULL,
            expiry_date DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "active",
            payment_status VARCHAR(20) NOT NULL DEFAULT "unpaid",
            payment_attempt_id CHAR(36) NULL,
            razorpay_order_id VARCHAR(120) NULL,
            razorpay_payment_id VARCHAR(120) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_student_subscriptions_student (student_id),
            INDEX idx_student_subscriptions_level (level_id),
            INDEX idx_student_subscriptions_status (status),
            INDEX idx_student_subscriptions_expiry (expiry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS subscription_reminders (
            id CHAR(36) PRIMARY KEY,
            subscription_id CHAR(36) NOT NULL,
            student_id CHAR(36) NOT NULL,
            reminder_type VARCHAR(30) NOT NULL,
            channel VARCHAR(20) NOT NULL,
            sent_to VARCHAR(255) NULL,
            message TEXT NULL,
            sent_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_subscription_reminder (subscription_id, reminder_type, channel),
            INDEX idx_subscription_reminders_student (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS student_courses (
            id CHAR(36) PRIMARY KEY,
            student_id CHAR(36) NOT NULL,
            course_id CHAR(36) NULL,
            level_id CHAR(36) NULL,
            subscription_id CHAR(36) NOT NULL,
            payment_attempt_id CHAR(36) NULL,
            status VARCHAR(20) NOT NULL DEFAULT "active",
            access_start DATETIME NOT NULL,
            access_end DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_student_courses_subscription (subscription_id),
            INDEX idx_student_courses_student (student_id),
            INDEX idx_student_courses_level (level_id),
            INDEX idx_student_courses_status (status),
            INDEX idx_student_courses_access_end (access_end)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS payment_audit_logs (
            id CHAR(36) PRIMARY KEY,
            student_id CHAR(36) NULL,
            payment_attempt_id CHAR(36) NULL,
            subscription_id CHAR(36) NULL,
            level_id CHAR(36) NULL,
            event VARCHAR(80) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT "info",
            message TEXT NULL,
            metadata_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_payment_audit_student (student_id),
            INDEX idx_payment_audit_attempt (payment_attempt_id),
            INDEX idx_payment_audit_subscription (subscription_id),
            INDEX idx_payment_audit_event (event),
            INDEX idx_payment_audit_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql('UPDATE student_subscriptions SET payment_status = "unpaid" WHERE payment_status = "pending"');

    if (!billing_table_has_column('courses', 'id')) {
        db_exec_sql(
            'CREATE TABLE IF NOT EXISTS courses (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!billing_table_has_column('levels', 'course_id')) {
        db_exec_sql('ALTER TABLE levels ADD COLUMN course_id CHAR(36) NULL AFTER level_name');
    }

    if (!billing_table_has_column('subscription_plans', 'level_id')) {
        db_exec_sql('ALTER TABLE subscription_plans ADD COLUMN level_id CHAR(36) NULL AFTER name');
    }
    if (!billing_table_has_column('subscription_plans', 'currency')) {
        db_exec_sql('ALTER TABLE subscription_plans ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT "INR" AFTER price');
    }
    if (!billing_table_has_column('subscription_plans', 'is_active')) {
        db_exec_sql('ALTER TABLE subscription_plans ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER currency');
    }

    ensure_worksheet_subscription_plans();

    $done = true;
}

function ensure_worksheet_subscription_plans(): void
{
    $now = now_sql();
    $courses = [
        [
            'name' => 'Abacus Worksheet',
            'slug' => 'abacus-worksheet',
            'description' => 'Abacus worksheet subscription for students',
            'levels' => ['Level 0 (Foundation)', 1, 2, 3, 4, 5, 6, 7],
        ],
        [
            'name' => 'Vedic Maths Worksheet',
            'slug' => 'vedic-maths-worksheet',
            'description' => 'Vedic Maths worksheet subscription for students',
            'levels' => [1, 2, 3, 4],
        ],
    ];
    $durations = [
        ['label' => '3 Months', 'days' => 90, 'price' => 99],
        ['label' => '1 Year', 'days' => 365, 'price' => 199],
    ];

    foreach ($courses as $courseData) {
        $course = db_one('SELECT * FROM courses WHERE slug = :slug LIMIT 1', ['slug' => $courseData['slug']]);
        if (!$course) {
            $courseId = uuid_v4();
            db_exec_sql(
                'INSERT INTO courses (id, name, slug, description, created_at, updated_at)
                 VALUES (:id, :name, :slug, :description, :created_at, :updated_at)',
                [
                    'id' => $courseId,
                    'name' => $courseData['name'],
                    'slug' => $courseData['slug'],
                    'description' => $courseData['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $course = ['id' => $courseId];
        }

        foreach ($courseData['levels'] as $levelNumber) {
            $levelLabel = is_numeric($levelNumber) ? 'Level ' . $levelNumber : (string) $levelNumber;
            $levelName = $courseData['name'] . ' ' . $levelLabel;
            $level = db_one(
                'SELECT * FROM levels WHERE course_id = :course_id AND level_name = :level_name LIMIT 1',
                ['course_id' => $course['id'], 'level_name' => $levelName]
            );
            if (!$level) {
                $levelId = uuid_v4();
                db_exec_sql(
                    'INSERT INTO levels (id, level_name, course_id, duration, description, created_at, updated_at)
                     VALUES (:id, :level_name, :course_id, :duration, :description, :created_at, :updated_at)',
                    [
                        'id' => $levelId,
                        'level_name' => $levelName,
                        'course_id' => $course['id'],
                        'duration' => 0,
                        'description' => $courseData['name'] . ' ' . $levelLabel,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
                $level = ['id' => $levelId];
            }

            foreach ($durations as $duration) {
                $planName = $courseData['name'] . ' ' . $levelLabel . ' - ' . $duration['label'];
                $plan = db_one('SELECT id FROM subscription_plans WHERE name = :name LIMIT 1', ['name' => $planName]);
                if ($plan) {
                    db_exec_sql(
                        'UPDATE subscription_plans
                         SET level_id = :level_id, duration_days = :duration_days, price = :price, currency = :currency,
                             is_active = :is_active, updated_at = :updated_at
                         WHERE id = :id',
                        [
                            'level_id' => $level['id'],
                            'duration_days' => $duration['days'],
                            'price' => $duration['price'],
                            'currency' => 'INR',
                            'is_active' => 1,
                            'updated_at' => $now,
                            'id' => $plan['id'],
                        ]
                    );
                } else {
                    db_exec_sql(
                        'INSERT INTO subscription_plans
                         (id, name, level_id, duration_days, price, currency, is_active, created_at, updated_at)
                         VALUES (:id, :name, :level_id, :duration_days, :price, :currency, :is_active, :created_at, :updated_at)',
                        [
                            'id' => uuid_v4(),
                            'name' => $planName,
                            'level_id' => $level['id'],
                            'duration_days' => $duration['days'],
                            'price' => $duration['price'],
                            'currency' => 'INR',
                            'is_active' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }
    }
}

function ensure_single_worksheet_subscription_plan(string $courseSlug, string $levelLabel, int $durationDays): ?array
{
    ensure_billing_schema();

    $duration = match ($durationDays) {
        90 => ['label' => '3 Months', 'days' => 90, 'price' => 99],
        365 => ['label' => '1 Year', 'days' => 365, 'price' => 199],
        default => null,
    };
    if (!$duration) {
        return null;
    }

    $course = db_one('SELECT * FROM courses WHERE slug = :slug LIMIT 1', ['slug' => $courseSlug]);
    if (!$course) {
        ensure_worksheet_subscription_plans();
        $course = db_one('SELECT * FROM courses WHERE slug = :slug LIMIT 1', ['slug' => $courseSlug]);
    }
    if (!$course) {
        return null;
    }

    $courseName = (string) ($course['name'] ?? ($courseSlug === 'vedic-maths-worksheet' ? 'Vedic Maths Worksheet' : 'Abacus Worksheet'));
    $normalizedLevel = trim($levelLabel);
    if (preg_match('/\bfoundation\b/i', $normalizedLevel) === 1) {
        $normalizedLevel = 'Level 0 (Foundation)';
    } elseif (preg_match('/\d+/', $normalizedLevel, $m) === 1) {
        $normalizedLevel = 'Level ' . $m[0];
    }

    $levelName = $courseName . ' ' . $normalizedLevel;
    $level = db_one(
        'SELECT * FROM levels WHERE course_id = :course_id AND level_name = :level_name LIMIT 1',
        ['course_id' => $course['id'], 'level_name' => $levelName]
    );
    if (!$level) {
        $levelId = uuid_v4();
        $now = now_sql();
        db_exec_sql(
            'INSERT INTO levels (id, level_name, course_id, duration, description, created_at, updated_at)
             VALUES (:id, :level_name, :course_id, :duration, :description, :created_at, :updated_at)',
            [
                'id' => $levelId,
                'level_name' => $levelName,
                'course_id' => $course['id'],
                'duration' => 0,
                'description' => $levelName,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $level = ['id' => $levelId, 'level_name' => $levelName, 'course_id' => $course['id']];
    }

    $planName = $courseName . ' ' . $normalizedLevel . ' - ' . $duration['label'];
    $plan = db_one('SELECT * FROM subscription_plans WHERE name = :name LIMIT 1', ['name' => $planName]);
    $now = now_sql();
    if ($plan) {
        db_exec_sql(
            'UPDATE subscription_plans
             SET level_id = :level_id, duration_days = :duration_days, price = :price, currency = :currency,
                 is_active = 1, updated_at = :updated_at
             WHERE id = :id',
            [
                'level_id' => $level['id'],
                'duration_days' => $duration['days'],
                'price' => $duration['price'],
                'currency' => 'INR',
                'updated_at' => $now,
                'id' => $plan['id'],
            ]
        );
        $plan['level_id'] = $level['id'];
        $plan['duration_days'] = $duration['days'];
        $plan['price'] = $duration['price'];
        $plan['currency'] = 'INR';
        $plan['is_active'] = 1;
        return $plan;
    }

    $planId = uuid_v4();
    db_exec_sql(
        'INSERT INTO subscription_plans
         (id, name, level_id, duration_days, price, currency, is_active, created_at, updated_at)
         VALUES (:id, :name, :level_id, :duration_days, :price, :currency, 1, :created_at, :updated_at)',
        [
            'id' => $planId,
            'name' => $planName,
            'level_id' => $level['id'],
            'duration_days' => $duration['days'],
            'price' => $duration['price'],
            'currency' => 'INR',
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    return db_one('SELECT * FROM subscription_plans WHERE id = :id LIMIT 1', ['id' => $planId]);
}

function billing_secret_key(): string
{
    $raw = (string) envv('APP_KEY', (string) envv('JWT_SECRET', 'change_this_secret'));
    if (str_starts_with($raw, 'base64:')) {
        $decoded = base64_decode(substr($raw, 7), true);
        if (is_string($decoded) && $decoded !== '') {
            $raw = $decoded;
        }
    }
    return hash('sha256', $raw, true);
}

function encrypt_sensitive_value(string $plain): string
{
    if ($plain === '') {
        return '';
    }

    $key = billing_secret_key();
    $iv = random_bytes(16);
    $cipherRaw = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($cipherRaw === false) {
        return '';
    }
    return base64_encode($iv . $cipherRaw);
}

function decrypt_sensitive_value(?string $encoded): string
{
    if (!is_string($encoded) || trim($encoded) === '') {
        return '';
    }

    $raw = base64_decode($encoded, true);
    if (!is_string($raw) || strlen($raw) <= 16) {
        return '';
    }

    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', billing_secret_key(), OPENSSL_RAW_DATA, $iv);
    return is_string($plain) ? $plain : '';
}

function get_payment_gateway_config(string $provider): array
{
    ensure_billing_schema();

    $provider = strtolower(trim($provider));
    $row = db_one('SELECT * FROM payment_gateway_configs WHERE provider = :provider LIMIT 1', ['provider' => $provider]);

    $envKeyId = (string) envv(strtoupper($provider) . '_KEY_ID', '');
    $envSecret = (string) envv(strtoupper($provider) . '_KEY_SECRET', '');

    $keyId = $row ? (string) ($row['key_id'] ?? '') : '';
    $secret = $row ? decrypt_sensitive_value($row['secret_enc'] ?? '') : '';
    $enabled = $row ? ((int) ($row['enabled'] ?? 0) === 1) : true;

    if ($keyId === '' && $envKeyId !== '') {
        $keyId = $envKeyId;
    }
    if ($secret === '' && $envSecret !== '') {
        $secret = $envSecret;
    }

    return [
        'provider' => $provider,
        'key_id' => $keyId,
        'secret' => $secret,
        'enabled' => $enabled,
        'configured' => $keyId !== '' && $secret !== '' && $enabled,
        'has_secret' => $secret !== '',
    ];
}

function sync_student_subscription_state(string $studentId): ?array
{
    ensure_billing_schema();

    $now = now_sql();
    db_exec_sql(
        'UPDATE student_subscriptions
         SET status = "expired", updated_at = :updated_at
         WHERE student_id = :student_id AND status = "active" AND expiry_date < :now_ts',
        ['updated_at' => $now, 'student_id' => $studentId, 'now_ts' => $now]
    );

    $active = db_one(
        'SELECT ss.*, l.level_name
         FROM student_subscriptions ss
         LEFT JOIN levels l ON l.id = ss.level_id
         WHERE ss.student_id = :student_id AND ss.status = "active" AND ss.payment_status IN ("paid", "captured", "success") AND ss.expiry_date >= :now_ts
         ORDER BY ss.expiry_date DESC
         LIMIT 1',
        ['student_id' => $studentId, 'now_ts' => $now]
    );

    if ($active) {
        $studentProfileLevelId = billing_student_assignable_level_id($active['level_id'] ?: null);
        db_exec_sql(
            'UPDATE students
             SET level_id = :level_id, subscription_plan = :plan_name, subscription_start = :start_date, subscription_end = :end_date,
                 subscription_status = :status, updated_at = :updated_at
             WHERE id = :student_id',
            [
                'level_id' => $studentProfileLevelId,
                'plan_name' => $active['plan_name'],
                'start_date' => $active['start_date'],
                'end_date' => $active['expiry_date'],
                'status' => 'active',
                'updated_at' => $now,
                'student_id' => $studentId,
            ]
        );
        return $active;
    }

    db_exec_sql(
        'UPDATE students
         SET subscription_status = :status, updated_at = :updated_at
         WHERE id = :student_id',
        ['status' => 'expired', 'updated_at' => $now, 'student_id' => $studentId]
    );

    return null;
}

function razorpay_request(string $method, string $path, array $payload, string $keyId, string $secret): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 500, 'message' => 'cURL extension is required for Razorpay integration'];
    }

    $url = 'https://api.razorpay.com/v1/' . ltrim($path, '/');
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'status' => 500, 'message' => 'Unable to initialize cURL'];
    }

    $headers = ['Accept: application/json'];
    $method = strtoupper($method);
    if ($method !== 'GET') {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $keyId . ':' . $secret,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    $resp = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'status' => 502, 'message' => $err !== '' ? $err : 'Payment gateway request failed'];
    }

    $decoded = json_decode((string) $resp, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'status' => 502, 'message' => 'Invalid payment gateway response'];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $message = (string) ($decoded['error']['description'] ?? $decoded['error']['reason'] ?? 'Payment gateway request failed');
        return ['ok' => false, 'status' => $httpCode > 0 ? $httpCode : 502, 'message' => $message, 'data' => $decoded];
    }

    return ['ok' => true, 'status' => $httpCode, 'data' => $decoded];
}

function payment_attempt_metadata(array $attempt): array
{
    $metadata = json_decode((string) ($attempt['metadata_json'] ?? ''), true);
    return is_array($metadata) ? $metadata : [];
}

function update_payment_attempt_metadata(string $attemptId, array $metadata): void
{
    db_exec_sql(
        'UPDATE payment_attempts SET metadata_json = :metadata_json, updated_at = :updated_at WHERE id = :id',
        [
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'updated_at' => now_sql(),
            'id' => $attemptId,
        ]
    );
}

function record_payment_gateway_payload(array $attempt, string $key, array $payload): void
{
    $metadata = payment_attempt_metadata($attempt);
    $metadata[$key] = $payload;
    update_payment_attempt_metadata((string) $attempt['id'], $metadata);
}

function fetch_razorpay_payment_status(string $paymentId, array $gateway): array
{
    if ($paymentId === '' || ($gateway['key_id'] ?? '') === '' || ($gateway['secret'] ?? '') === '') {
        return ['ok' => false, 'message' => 'Razorpay credentials or payment id missing'];
    }

    return razorpay_request(
        'GET',
        '/payments/' . rawurlencode($paymentId),
        [],
        (string) $gateway['key_id'],
        (string) $gateway['secret']
    );
}

function map_subscription_row(array $row): array
{
    return [
        'id' => $row['id'],
        'planId' => $row['plan_id'] ?? null,
        'planName' => $row['plan_name'] ?? '',
        'levelId' => $row['level_id'] ?? null,
        'levelName' => $row['level_name'] ?? null,
        'amount' => (float) ($row['amount'] ?? 0),
        'currency' => $row['currency'] ?? 'INR',
        'startDate' => $row['start_date'] ?? null,
        'expiryDate' => $row['expiry_date'] ?? null,
        'status' => $row['status'] ?? 'expired',
        'paymentStatus' => in_array((string) ($row['payment_status'] ?? ''), ['paid', 'captured', 'success'], true) ? 'paid' : 'unpaid',
        'razorpayOrderId' => $row['razorpay_order_id'] ?? null,
        'razorpayPaymentId' => $row['razorpay_payment_id'] ?? null,
        'createdAt' => $row['created_at'] ?? null,
        'updatedAt' => $row['updated_at'] ?? null,
    ];
}

function billing_table_type(string $table): string
{
    return (string) db_value(
        'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1',
        ['table' => $table]
    );
}

function billing_student_assignable_level_id(?string $levelId): ?string
{
    $levelId = trim((string) $levelId);
    if ($levelId === '') {
        return null;
    }

    if (billing_table_type('student') === 'BASE TABLE' && billing_table_has_column('student', 'levelId')) {
        $existsInLegacyLevel = (int) db_value('SELECT COUNT(*) FROM level WHERE id = :id', ['id' => $levelId]);
        return $existsInLegacyLevel > 0 ? $levelId : null;
    }

    return $levelId;
}

function payment_audit_log(
    string $event,
    string $status = 'info',
    ?string $studentId = null,
    ?string $attemptId = null,
    ?string $subscriptionId = null,
    ?string $levelId = null,
    string $message = '',
    array $metadata = []
): void {
    try {
        db_exec_sql(
            'INSERT INTO payment_audit_logs
             (id, student_id, payment_attempt_id, subscription_id, level_id, event, status, message, metadata_json, created_at)
             VALUES
             (:id, :student_id, :payment_attempt_id, :subscription_id, :level_id, :event, :status, :message, :metadata_json, :created_at)',
            [
                'id' => uuid_v4(),
                'student_id' => $studentId,
                'payment_attempt_id' => $attemptId,
                'subscription_id' => $subscriptionId,
                'level_id' => $levelId,
                'event' => $event,
                'status' => $status,
                'message' => $message !== '' ? $message : null,
                'metadata_json' => $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null,
                'created_at' => now_sql(),
            ]
        );
    } catch (Throwable $e) {
        error_log('Payment audit log failed: ' . $e->getMessage());
    }
}

function sync_paid_subscription_enrollment(string $subscriptionId): void
{
    ensure_billing_schema();

    $row = db_one(
        'SELECT ss.*, l.course_id
         FROM student_subscriptions ss
         LEFT JOIN levels l ON l.id = ss.level_id
         WHERE ss.id = :id AND ss.payment_status IN ("paid", "captured", "success") AND ss.status = "active"
         LIMIT 1',
        ['id' => $subscriptionId]
    );
    if (!$row) {
        return;
    }

    $now = now_sql();
    $existing = db_one(
        'SELECT id FROM student_courses WHERE subscription_id = :subscription_id LIMIT 1',
        ['subscription_id' => $subscriptionId]
    );

    if ($existing) {
        db_exec_sql(
            'UPDATE student_courses
             SET student_id = :student_id, course_id = :course_id, level_id = :level_id,
                 payment_attempt_id = :payment_attempt_id, status = :status, access_start = :access_start,
                 access_end = :access_end, updated_at = :updated_at
             WHERE id = :id',
            [
                'student_id' => $row['student_id'],
                'course_id' => $row['course_id'] ?: null,
                'level_id' => $row['level_id'] ?: null,
                'payment_attempt_id' => $row['payment_attempt_id'] ?: null,
                'status' => 'active',
                'access_start' => $row['start_date'],
                'access_end' => $row['expiry_date'],
                'updated_at' => $now,
                'id' => $existing['id'],
            ]
        );
        payment_audit_log(
            'Course Assigned',
            'success',
            (string) $row['student_id'],
            $row['payment_attempt_id'] ?: null,
            $subscriptionId,
            $row['level_id'] ?: null,
            'Existing course allocation refreshed.'
        );
        return;
    }

    db_exec_sql(
        'INSERT INTO student_courses
         (id, student_id, course_id, level_id, subscription_id, payment_attempt_id, status, access_start, access_end, created_at, updated_at)
         VALUES
         (:id, :student_id, :course_id, :level_id, :subscription_id, :payment_attempt_id, :status, :access_start, :access_end, :created_at, :updated_at)',
        [
            'id' => uuid_v4(),
            'student_id' => $row['student_id'],
            'course_id' => $row['course_id'] ?: null,
            'level_id' => $row['level_id'] ?: null,
            'subscription_id' => $subscriptionId,
            'payment_attempt_id' => $row['payment_attempt_id'] ?: null,
            'status' => 'active',
            'access_start' => $row['start_date'],
            'access_end' => $row['expiry_date'],
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    payment_audit_log(
        'Course Assigned',
        'success',
        (string) $row['student_id'],
        $row['payment_attempt_id'] ?: null,
        $subscriptionId,
        $row['level_id'] ?: null,
        'Course allocation created.'
    );
    payment_audit_log(
        'Worksheet Access Granted',
        'success',
        (string) $row['student_id'],
        $row['payment_attempt_id'] ?: null,
        $subscriptionId,
        $row['level_id'] ?: null,
        'Worksheet/practice access granted for purchased level.'
    );
}

function repair_student_course_enrollments(string $studentId): void
{
    ensure_billing_schema();

    $rows = db_all(
        'SELECT id FROM student_subscriptions
         WHERE student_id = :student_id AND status = "active" AND payment_status IN ("paid", "captured", "success") AND expiry_date >= :now_ts',
        ['student_id' => $studentId, 'now_ts' => now_sql()]
    );

    foreach ($rows as $row) {
        try {
            sync_paid_subscription_enrollment((string) $row['id']);
        } catch (Throwable $e) {
            error_log('Course assignment failed for subscription ' . ($row['id'] ?? '') . ': ' . $e->getMessage());
            payment_audit_log(
                'Course Assigned',
                'failed',
                $studentId,
                null,
                (string) ($row['id'] ?? ''),
                null,
                $e->getMessage()
            );
            throw $e;
        }
    }
}

function paid_attempt_plan_ids(array $attempt): array
{
    $metadata = json_decode((string) ($attempt['metadata_json'] ?? ''), true);
    $planIds = [];
    if (is_array($metadata) && isset($metadata['plan_ids']) && is_array($metadata['plan_ids'])) {
        $planIds = array_values(array_filter(array_map(static fn($id): string => trim((string) $id), $metadata['plan_ids'])));
    }
    if (!$planIds && !empty($attempt['plan_id'])) {
        $planIds = [(string) $attempt['plan_id']];
    }
    return array_values(array_unique($planIds));
}

function activateWorksheetSubscription(array $payload): array
{
    ensure_billing_schema();

    $studentId = trim((string) ($payload['userId'] ?? $payload['studentId'] ?? ''));
    $planId = trim((string) ($payload['planId'] ?? ''));
    $levelId = trim((string) ($payload['levelId'] ?? ''));
    $orderId = trim((string) ($payload['orderId'] ?? ''));
    $paymentId = trim((string) ($payload['paymentId'] ?? ''));
    $paymentStatus = strtolower(trim((string) ($payload['paymentStatus'] ?? 'captured')));
    $gateway = strtolower(trim((string) ($payload['gateway'] ?? 'razorpay')));
    $attemptId = trim((string) ($payload['paymentAttemptId'] ?? ''));
    $signature = trim((string) ($payload['signature'] ?? ''));

    if ($studentId === '') {
        throw new RuntimeException('Subscription activation failed: user_id/student_id is missing.');
    }
    if (function_exists('ensure_student_profile_for_user_id')) {
        $resolvedStudent = ensure_student_profile_for_user_id($studentId, true);
        if ($resolvedStudent && !empty($resolvedStudent['id'])) {
            $studentId = (string) $resolvedStudent['id'];
        }
    }
    if ($planId === '') {
        throw new RuntimeException('Subscription activation failed: plan_id is missing.');
    }
    if ($orderId === '') {
        throw new RuntimeException('Subscription activation failed: order_id is missing.');
    }
    if ($paymentId === '') {
        throw new RuntimeException('Subscription activation failed: payment_id is missing.');
    }
    if (!in_array($paymentStatus, ['paid', 'captured', 'success'], true)) {
        throw new RuntimeException('Subscription activation blocked: payment status is ' . ($paymentStatus !== '' ? $paymentStatus : 'unknown') . ', not captured.');
    }

    $plan = db_one('SELECT * FROM subscription_plans WHERE id = :id LIMIT 1', ['id' => $planId]);
    if (!$plan) {
        throw new RuntimeException('Subscription activation failed: subscription plan was not found.');
    }
    if ($levelId === '') {
        $levelId = (string) ($plan['level_id'] ?? '');
    }
    if ($levelId === '') {
        throw new RuntimeException('Subscription activation failed: level_id is missing for purchased worksheet plan.');
    }

    $attempt = null;
    if ($attemptId !== '') {
        $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attemptId]);
    }
    if (!$attempt && $gateway !== '' && $orderId !== '') {
        $attempt = db_one(
            'SELECT * FROM payment_attempts WHERE provider = :provider AND provider_order_id = :order_id LIMIT 1',
            ['provider' => $gateway, 'order_id' => $orderId]
        );
    }

    $now = now_sql();
    $metadata = [];
    if ($attempt && !empty($attempt['metadata_json'])) {
        $decoded = json_decode((string) $attempt['metadata_json'], true);
        if (is_array($decoded)) {
            $metadata = $decoded;
        }
    }
    $metadata['plan_ids'] = array_values(array_unique(array_filter(array_merge(
        isset($metadata['plan_ids']) && is_array($metadata['plan_ids']) ? $metadata['plan_ids'] : [],
        [$planId]
    ))));
    $metadata['product_id'] = $payload['productId'] ?? ($metadata['product_id'] ?? null);
    $metadata['program_type'] = $payload['programType'] ?? ($metadata['program_type'] ?? (stripos((string) ($plan['name'] ?? ''), 'vedic') !== false ? 'vedic' : 'abacus'));
    $metadata['level_id'] = $levelId;
    $metadata['gateway'] = $gateway;
    $metadata['payment_status'] = $paymentStatus;

    if ($attempt) {
        $attemptId = (string) $attempt['id'];
        db_exec_sql(
            'UPDATE payment_attempts
             SET student_id = :student_id, plan_id = :plan_id, provider = :provider, amount = :amount, currency = :currency,
                 status = :status, allocation_status = :allocation_status, allocation_error = NULL,
                 provider_order_id = :order_id, provider_payment_id = :payment_id, provider_signature = :signature,
                 metadata_json = :metadata_json, paid_at = COALESCE(paid_at, :paid_at), updated_at = :updated_at
             WHERE id = :id',
            [
                'student_id' => $studentId,
                'plan_id' => $planId,
                'provider' => $gateway,
                'amount' => (float) ($plan['price'] ?? ($attempt['amount'] ?? 0)),
                'currency' => $plan['currency'] ?: ($attempt['currency'] ?: 'INR'),
                'status' => 'paid',
                'allocation_status' => 'assigning',
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'signature' => $signature !== '' ? $signature : ($attempt['provider_signature'] ?? null),
                'metadata_json' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
                'paid_at' => $payload['startDate'] ?? $now,
                'updated_at' => $now,
                'id' => $attemptId,
            ]
        );
    } else {
        $attemptId = uuid_v4();
        db_exec_sql(
            'INSERT INTO payment_attempts
             (id, student_id, plan_id, provider, amount, currency, status, allocation_status, provider_order_id, provider_payment_id, provider_signature, metadata_json, paid_at, created_at, updated_at)
             VALUES
             (:id, :student_id, :plan_id, :provider, :amount, :currency, :status, :allocation_status, :order_id, :payment_id, :signature, :metadata_json, :paid_at, :created_at, :updated_at)',
            [
                'id' => $attemptId,
                'student_id' => $studentId,
                'plan_id' => $planId,
                'provider' => $gateway,
                'amount' => (float) ($plan['price'] ?? 0),
                'currency' => $plan['currency'] ?: 'INR',
                'status' => 'paid',
                'allocation_status' => 'assigning',
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'signature' => $signature !== '' ? $signature : null,
                'metadata_json' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
                'paid_at' => $payload['startDate'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    payment_audit_log(
        'Shared Subscription Activation',
        'info',
        $studentId,
        $attemptId,
        null,
        $levelId,
        'Running production-safe worksheet subscription activation service.',
        [
            'productId' => $payload['productId'] ?? null,
            'planId' => $planId,
            'programType' => $metadata['program_type'] ?? null,
            'paymentId' => $paymentId,
            'orderId' => $orderId,
            'gateway' => $gateway,
            'paymentStatus' => $paymentStatus,
            'startDate' => $payload['startDate'] ?? null,
            'endDate' => $payload['endDate'] ?? null,
        ]
    );

    $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attemptId]);
    if (!$attempt) {
        throw new RuntimeException('Subscription activation failed: payment record could not be loaded after save.');
    }

    create_missing_subscriptions_for_paid_attempt($attempt);
    repair_paid_payment_attempt_subscriptions($studentId);
    repair_student_course_enrollments($studentId);

    $subscription = db_one(
        'SELECT * FROM student_subscriptions WHERE payment_attempt_id = :payment_attempt_id ORDER BY created_at DESC LIMIT 1',
        ['payment_attempt_id' => $attemptId]
    );
    if (!$subscription) {
        throw new RuntimeException('Subscription activation failed: subscription row was not created for captured payment.');
    }

    payment_audit_log(
        'Worksheet Access Granted',
        'success',
        $studentId,
        $attemptId,
        (string) $subscription['id'],
        $levelId,
        'Purchased worksheet level is active after shared activation service.',
        ['planId' => $planId, 'paymentId' => $paymentId, 'orderId' => $orderId]
    );

    return get_student_subscription_overview($studentId);
}
function create_missing_subscriptions_for_paid_attempt(array $attempt): void
{
    ensure_billing_schema();

    if (!in_array((string) ($attempt['status'] ?? ''), ['paid', 'captured', 'success'], true)) {
        return;
    }

    $attemptId = (string) ($attempt['id'] ?? '');
    $studentId = (string) ($attempt['student_id'] ?? '');
    if ($attemptId === '' || $studentId === '') {
        return;
    }

    $existingCount = (int) db_value(
        'SELECT COUNT(*) FROM student_subscriptions WHERE payment_attempt_id = :payment_attempt_id',
        ['payment_attempt_id' => $attemptId]
    );
    if ($existingCount > 0) {
        $existingSubs = db_all(
            'SELECT ss.*, p.duration_days, p.level_id AS plan_level_id, p.name AS plan_name_ref, p.price AS plan_price, p.currency AS plan_currency
             FROM student_subscriptions ss
             LEFT JOIN subscription_plans p ON p.id = ss.plan_id
             WHERE ss.payment_attempt_id = :payment_attempt_id',
            ['payment_attempt_id' => $attemptId]
        );
        $paidAt = (string) ($attempt['paid_at'] ?? $attempt['created_at'] ?? '');
        $paidTs = strtotime($paidAt);
        if ($paidTs === false) {
            $paidTs = time();
        }
        $now = now_sql();
        foreach ($existingSubs as $sub) {
            $days = max(1, (int) ($sub['duration_days'] ?? 90));
            $startTs = strtotime((string) ($sub['start_date'] ?? ''));
            if ($startTs === false) {
                $startTs = $paidTs;
            }
            $expiryTs = strtotime((string) ($sub['expiry_date'] ?? ''));
            if ($expiryTs === false || $expiryTs < time()) {
                $expiryTs = max($startTs, $paidTs) + ($days * 86400);
            }
            $levelId = (string) ($sub['level_id'] ?: ($sub['plan_level_id'] ?? ''));
            db_exec_sql(
                'UPDATE student_subscriptions
                 SET level_id = :level_id, status = :status, payment_status = :payment_status,
                     start_date = :start_date, expiry_date = :expiry_date,
                     razorpay_order_id = :razorpay_order_id, razorpay_payment_id = :razorpay_payment_id,
                     updated_at = :updated_at
                 WHERE id = :id',
                [
                    'level_id' => $levelId !== '' ? $levelId : null,
                    'status' => 'active',
                    'payment_status' => 'paid',
                    'start_date' => gmdate('Y-m-d H:i:s', $startTs),
                    'expiry_date' => gmdate('Y-m-d H:i:s', $expiryTs),
                    'razorpay_order_id' => $attempt['provider_order_id'] ?? ($sub['razorpay_order_id'] ?? null),
                    'razorpay_payment_id' => $attempt['provider_payment_id'] ?? ($sub['razorpay_payment_id'] ?? null),
                    'updated_at' => $now,
                    'id' => $sub['id'],
                ]
            );
            sync_paid_subscription_enrollment((string) $sub['id']);
            payment_audit_log(
                'Subscription Repaired',
                'success',
                $studentId,
                $attemptId,
                (string) $sub['id'],
                $levelId !== '' ? $levelId : null,
                'Existing subscription row repaired for captured payment.',
                [
                    'userId' => $studentId,
                    'paymentId' => $attempt['provider_payment_id'] ?? null,
                    'orderId' => $attempt['provider_order_id'] ?? null,
                    'startDate' => gmdate('Y-m-d H:i:s', $startTs),
                    'expiryDate' => gmdate('Y-m-d H:i:s', $expiryTs),
                ]
            );
        }
        repair_student_course_enrollments($studentId);
        db_exec_sql(
            'UPDATE payment_attempts SET status = :status, allocation_status = :allocation_status, allocation_error = NULL, updated_at = :updated_at WHERE id = :id',
            ['status' => 'paid', 'allocation_status' => 'assigned', 'updated_at' => now_sql(), 'id' => $attemptId]
        );
        return;
    }

    $planIds = paid_attempt_plan_ids($attempt);
    if (!$planIds) {
        error_log('Paid payment attempt has no plan ids: ' . $attemptId);
        db_exec_sql(
            'UPDATE payment_attempts SET allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at WHERE id = :id',
            ['allocation_status' => 'failed', 'allocation_error' => 'Paid payment attempt has no plan ids.', 'updated_at' => now_sql(), 'id' => $attemptId]
        );
        payment_audit_log('Course Assigned', 'failed', $studentId, $attemptId, null, null, 'Paid payment attempt has no plan ids.');
        return;
    }

    $placeholders = implode(',', array_fill(0, count($planIds), '?'));
    $plans = db_all("SELECT * FROM subscription_plans WHERE id IN ({$placeholders})", $planIds);
    if (count($plans) !== count($planIds)) {
        error_log('Paid payment attempt has missing subscription plans: ' . $attemptId);
        db_exec_sql(
            'UPDATE payment_attempts SET allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at WHERE id = :id',
            ['allocation_status' => 'failed', 'allocation_error' => 'Paid payment attempt has missing subscription plans.', 'updated_at' => now_sql(), 'id' => $attemptId]
        );
        payment_audit_log('Course Assigned', 'failed', $studentId, $attemptId, null, null, 'Paid payment attempt has missing subscription plans.', ['planIds' => $planIds]);
        return;
    }

    $paidAt = (string) ($attempt['paid_at'] ?? $attempt['created_at'] ?? '');
    $paidTs = strtotime($paidAt);
    if ($paidTs === false) {
        $paidTs = time();
    }

    $now = now_sql();
    $orderId = (string) ($attempt['provider_order_id'] ?? '');
    $paymentId = (string) ($attempt['provider_payment_id'] ?? '');

    foreach ($plans as $plan) {
        $days = (int) ($plan['duration_days'] ?? 0);
        if ($days <= 0) {
            error_log('Paid payment attempt has invalid plan duration: ' . $attemptId);
            payment_audit_log('Course Assigned', 'failed', $studentId, $attemptId, null, $plan['level_id'] ?? null, 'Paid payment attempt has invalid plan duration.', ['planId' => $plan['id'] ?? null]);
            continue;
        }

        $existing = db_one(
            'SELECT * FROM student_subscriptions
             WHERE student_id = :student_id AND level_id <=> :level_id AND status = "active" AND payment_status IN ("paid", "captured", "success")
             ORDER BY expiry_date DESC
             LIMIT 1',
            [
                'student_id' => $studentId,
                'level_id' => $plan['level_id'] ?? null,
            ]
        );

        $baseExpiryTs = $paidTs;
        if ($existing && !empty($existing['expiry_date'])) {
            $existingExpiry = strtotime((string) $existing['expiry_date']);
            if ($existingExpiry !== false && $existingExpiry > $baseExpiryTs) {
                $baseExpiryTs = $existingExpiry;
            }
        }

        $startDate = gmdate('Y-m-d H:i:s', $paidTs);
        $endDate = gmdate('Y-m-d H:i:s', $baseExpiryTs + ($days * 86400));

        db_exec_sql(
            'UPDATE student_subscriptions
             SET status = :status, updated_at = :updated_at
             WHERE student_id = :student_id AND level_id <=> :level_id AND status = "active"',
            ['status' => 'expired', 'updated_at' => $now, 'student_id' => $studentId, 'level_id' => $plan['level_id'] ?: null]
        );

        $subscriptionId = uuid_v4();
        db_exec_sql(
            'INSERT INTO student_subscriptions
             (id, student_id, plan_id, level_id, plan_name, amount, currency, start_date, expiry_date, status, payment_status, payment_attempt_id, razorpay_order_id, razorpay_payment_id, created_at, updated_at)
             VALUES
             (:id, :student_id, :plan_id, :level_id, :plan_name, :amount, :currency, :start_date, :expiry_date, :status, :payment_status, :payment_attempt_id, :razorpay_order_id, :razorpay_payment_id, :created_at, :updated_at)',
            [
                'id' => $subscriptionId,
                'student_id' => $studentId,
                'plan_id' => $plan['id'],
                'level_id' => $plan['level_id'] ?: null,
                'plan_name' => $plan['name'],
                'amount' => (float) ($plan['price'] ?? 0),
                'currency' => $plan['currency'] ?: ($attempt['currency'] ?: 'INR'),
                'start_date' => $startDate,
                'expiry_date' => $endDate,
                'status' => 'active',
                'payment_status' => 'paid',
                'payment_attempt_id' => $attemptId,
                'razorpay_order_id' => $orderId !== '' ? $orderId : null,
                'razorpay_payment_id' => $paymentId !== '' ? $paymentId : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        payment_audit_log(
            'User Subscription Created',
            'success',
            $studentId,
            $attemptId,
            $subscriptionId,
            $plan['level_id'] ?: null,
            'Paid payment repaired into active subscription.',
            ['planId' => $plan['id'], 'planName' => $plan['name'], 'paymentId' => $paymentId, 'orderId' => $orderId, 'startDate' => $startDate, 'expiryDate' => $endDate, 'levelId' => $plan['level_id'] ?: null, 'programType' => (stripos((string) ($plan['name'] ?? ''), 'vedic') !== false ? 'vedic' : 'abacus')]
        );

        sync_paid_subscription_enrollment($subscriptionId);

        db_exec_sql(
            'UPDATE students
             SET level_id = :level_id, subscription_plan = :plan_name, subscription_start = :start_date, subscription_end = :end_date, subscription_status = :status, updated_at = :updated_at
             WHERE id = :student_id',
            [
                'level_id' => billing_student_assignable_level_id($plan['level_id'] ?: null),
                'plan_name' => $plan['name'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'updated_at' => $now,
                'student_id' => $studentId,
            ]
        );
    }

    db_exec_sql(
        'UPDATE payment_attempts SET allocation_status = :allocation_status, allocation_error = NULL, updated_at = :updated_at WHERE id = :id',
        ['allocation_status' => 'assigned', 'updated_at' => now_sql(), 'id' => $attemptId]
    );
}

function mock_payment_enabled(): bool
{
    $env = strtolower(trim((string) envv('APP_ENV', '')));
    $enabled = strtolower(trim((string) envv('MOCK_PAYMENT_ENABLED', '')));
    return $env === 'development' && in_array($enabled, ['1', 'true', 'yes', 'on'], true);
}

function require_mock_payment_enabled(): void
{
    if (!mock_payment_enabled()) {
        json_response(['message' => 'Mock payment is disabled outside local development.'], 403);
    }
}

function mock_payment_level_options(): array
{
    $options = [
        ['key' => 'foundation', 'label' => 'Foundation', 'level' => 'Level 0 (Foundation)'],
    ];
    for ($i = 1; $i <= 7; $i++) {
        $options[] = ['key' => 'level-' . $i, 'label' => 'Level ' . $i, 'level' => 'Level ' . $i];
    }
    return $options;
}

function mock_payment_resolve_level(string $levelKey): array
{
    foreach (mock_payment_level_options() as $option) {
        if ($option['key'] === $levelKey) {
            return $option;
        }
    }
    json_response(['message' => 'Invalid mock payment level selected.'], 422);
}

function mock_payment_plan_for_level(string $levelKey): array
{
    $level = mock_payment_resolve_level($levelKey);
    $plan = ensure_single_worksheet_subscription_plan('abacus-worksheet', $level['level'], 90);
    if (!$plan) {
        json_response(['message' => 'Unable to create or find worksheet subscription plan for ' . $level['label']], 500);
    }
    return [$level, $plan];
}

function mock_payment_log(string $step, string $status, string $studentId, ?string $attemptId, ?string $subscriptionId, ?string $levelId, string $message, array $context = []): void
{
    error_log('[MockPayment] ' . $step . ' ' . $status . ' student=' . $studentId . ' ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
    payment_audit_log($step, $status, $studentId, $attemptId, $subscriptionId, $levelId, $message, $context);
}

function controller_dev_mock_payment_status(array $ctx): void
{
    require_mock_payment_enabled();
    json_response([
        'enabled' => true,
        'levels' => array_map(static fn(array $option): array => [
            'key' => $option['key'],
            'label' => $option['label'],
        ], mock_payment_level_options()),
    ]);
}

function controller_dev_mock_payment_activate(array $ctx, array $data): void
{
    require_mock_payment_enabled();
    ensure_billing_schema();

    $student = current_student((string) ($ctx['user']['id'] ?? ''));
    $studentId = (string) ($student['id'] ?? '');
    if ($studentId === '') {
        json_response(['message' => 'Logged-in student could not be resolved for mock payment.'], 401);
    }

    $levelKey = strtolower(trim((string) ($data['levelKey'] ?? 'foundation')));
    [$level, $plan] = mock_payment_plan_for_level($levelKey);

    $timestamp = (string) time();
    $attemptId = uuid_v4();
    $orderId = 'mock_order_' . $timestamp;
    $paymentId = 'mock_pay_' . $timestamp;
    $now = now_sql();
    $metadata = [
        'gateway' => 'mock',
        'payment_status' => 'success',
        'plan_ids' => [$plan['id']],
        'level_key' => $levelKey,
        'level_label' => $level['label'],
        'mock' => true,
    ];

    db_exec_sql(
        'INSERT INTO payment_attempts
         (id, student_id, plan_id, provider, amount, currency, status, allocation_status, provider_order_id, provider_payment_id, provider_signature, metadata_json, paid_at, created_at, updated_at)
         VALUES
         (:id, :student_id, :plan_id, :provider, :amount, :currency, :status, :allocation_status, :provider_order_id, :provider_payment_id, :provider_signature, :metadata_json, :paid_at, :created_at, :updated_at)',
        [
            'id' => $attemptId,
            'student_id' => $studentId,
            'plan_id' => $plan['id'],
            'provider' => 'mock',
            'amount' => (float) ($plan['price'] ?? 99),
            'currency' => $plan['currency'] ?: 'INR',
            'status' => 'paid',
            'allocation_status' => 'assigning',
            'provider_order_id' => $orderId,
            'provider_payment_id' => $paymentId,
            'provider_signature' => 'mock_signature_' . $timestamp,
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'paid_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    mock_payment_log('Payment Created', 'success', $studentId, $attemptId, null, $plan['level_id'] ?? null, 'Mock payment record created.', [
        'orderId' => $orderId,
        'paymentId' => $paymentId,
        'level' => $level['label'],
        'planId' => $plan['id'],
    ]);

    $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attemptId]);
    if (!$attempt) {
        json_response(['message' => 'Mock payment record was not found after creation.'], 500);
    }

    try {
        activateWorksheetSubscription([
            'userId' => $studentId,
            'productId' => $plan['course_id'] ?? null,
            'planId' => $plan['id'],
            'programType' => 'abacus',
            'levelId' => $plan['level_id'] ?? null,
            'paymentId' => $paymentId,
            'orderId' => $orderId,
            'paymentStatus' => 'success',
            'gateway' => 'mock',
            'startDate' => $now,
            'endDate' => null,
            'paymentAttemptId' => $attemptId,
            'signature' => 'mock_signature_' . $timestamp,
        ]);
    } catch (Throwable $e) {
        db_exec_sql(
            'UPDATE payment_attempts SET allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at WHERE id = :id',
            ['allocation_status' => 'failed', 'allocation_error' => substr($e->getMessage(), 0, 1000), 'updated_at' => now_sql(), 'id' => $attemptId]
        );
        mock_payment_log('Subscription Created', 'failed', $studentId, $attemptId, null, $plan['level_id'] ?? null, $e->getMessage(), ['level' => $level['label']]);
        json_response(['message' => 'Mock payment captured, but subscription activation failed: ' . $e->getMessage()], 500);
    }

    $subscription = db_one(
        'SELECT * FROM student_subscriptions WHERE payment_attempt_id = :payment_attempt_id ORDER BY created_at DESC LIMIT 1',
        ['payment_attempt_id' => $attemptId]
    );
    $paperCount = (int) db_value('SELECT COUNT(*) FROM worksheet_papers WHERE level_id = :level_id', ['level_id' => $plan['level_id'] ?? '']);
    $questionCount = (int) db_value(
        'SELECT COUNT(*)
         FROM worksheet_questions wq
         INNER JOIN worksheet_papers wp ON wp.id = wq.paper_id
         WHERE wp.level_id = :level_id',
        ['level_id' => $plan['level_id'] ?? '']
    );
    $overview = get_student_subscription_overview($studentId);

    mock_payment_log('Subscription Created', $subscription ? 'success' : 'failed', $studentId, $attemptId, $subscription['id'] ?? null, $plan['level_id'] ?? null, $subscription ? 'Mock payment activated subscription.' : 'Subscription row missing after activation.', [
        'startDate' => $subscription['start_date'] ?? null,
        'expiryDate' => $subscription['expiry_date'] ?? null,
    ]);
    mock_payment_log('Worksheet Mapping', $paperCount > 0 ? 'success' : 'failed', $studentId, $attemptId, $subscription['id'] ?? null, $plan['level_id'] ?? null, 'Worksheet mapping checked for purchased level.', [
        'paperCount' => $paperCount,
        'questionCount' => $questionCount,
    ]);
    mock_payment_log('Dashboard Data', 'success', $studentId, $attemptId, $subscription['id'] ?? null, $plan['level_id'] ?? null, 'Dashboard overview recalculated after mock payment.', [
        'subscriptions' => count($overview['subscription']['history'] ?? []),
    ]);

    json_response([
        'message' => 'Mock payment completed and subscription activated.',
        'gateway' => 'mock',
        'payment_status' => 'success',
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'attemptId' => $attemptId,
        'level' => $level,
        'plan' => [
            'id' => $plan['id'],
            'name' => $plan['name'],
            'levelId' => $plan['level_id'] ?? null,
            'levelName' => $plan['level_name'] ?? null,
        ],
        'subscription' => $subscription,
        'paperCount' => $paperCount,
        'questionCount' => $questionCount,
        'overview' => $overview,
    ]);
}

function controller_dev_mock_payment_clear(array $ctx, string $mode): void
{
    require_mock_payment_enabled();
    ensure_billing_schema();

    $student = current_student((string) ($ctx['user']['id'] ?? ''));
    $studentId = (string) ($student['id'] ?? '');
    if ($studentId === '') {
        json_response(['message' => 'Logged-in student could not be resolved for mock payment cleanup.'], 401);
    }

    $mockSubs = db_all(
        'SELECT ss.id
         FROM student_subscriptions ss
         INNER JOIN payment_attempts pa ON pa.id = ss.payment_attempt_id
         WHERE ss.student_id = :student_id AND pa.provider = "mock"',
        ['student_id' => $studentId]
    );
    $subIds = array_map(static fn(array $row): string => (string) $row['id'], $mockSubs);

    $deletedPractices = 0;
    if ($mode === 'clear-test-data') {
        $deletedPractices = db_exec_sql(
            'DELETE wpra
             FROM worksheet_practices wpra
             INNER JOIN worksheet_papers wp ON wp.id = wpra.topic_id
             WHERE wpra.student_id = :student_id',
            ['student_id' => $studentId]
        );
    }

    if ($subIds) {
        $placeholders = implode(',', array_fill(0, count($subIds), '?'));
        db_exec_sql("DELETE FROM student_courses WHERE subscription_id IN ({$placeholders})", $subIds);
        db_exec_sql("DELETE FROM student_subscriptions WHERE id IN ({$placeholders})", $subIds);
    }

    $deletedPayments = db_exec_sql(
        'DELETE FROM payment_attempts WHERE student_id = :student_id AND provider = "mock"',
        ['student_id' => $studentId]
    );

    mock_payment_log('Clear Test Data', 'success', $studentId, null, null, null, 'Mock payment test data cleared.', [
        'mode' => $mode,
        'subscriptionsDeleted' => count($subIds),
        'paymentsDeleted' => $deletedPayments,
        'practicesDeleted' => $deletedPractices,
    ]);

    json_response([
        'message' => 'Mock payment test data cleared.',
        'subscriptionsDeleted' => count($subIds),
        'paymentsDeleted' => $deletedPayments,
        'practicesDeleted' => $deletedPractices,
    ]);
}
function repair_paid_payment_attempt_subscriptions(string $studentId): void
{
    ensure_billing_schema();

    $attempts = db_all(
        'SELECT pa.*
         FROM payment_attempts pa
         WHERE pa.student_id = :student_id
           AND pa.status IN ("paid", "captured", "success")
           AND (
             NOT EXISTS (
               SELECT 1 FROM student_subscriptions ss WHERE ss.payment_attempt_id = pa.id
             )
             OR EXISTS (
               SELECT 1
               FROM student_subscriptions ss
               WHERE ss.payment_attempt_id = pa.id
                 AND (
                   ss.level_id IS NULL
                   OR ss.start_date IS NULL
                   OR ss.start_date = "0000-00-00 00:00:00"
                   OR ss.expiry_date IS NULL
                   OR ss.expiry_date = "0000-00-00 00:00:00"
                   OR ss.payment_status NOT IN ("paid", "captured", "success")
                   OR (ss.status <> "active" AND ss.expiry_date >= :now_ts)
                 )
             )
           )
         ORDER BY COALESCE(pa.paid_at, pa.created_at) ASC, pa.created_at ASC',
        ['student_id' => $studentId, 'now_ts' => now_sql()]
    );

    foreach ($attempts as $attempt) {
        try {
            create_missing_subscriptions_for_paid_attempt($attempt);
        } catch (Throwable $e) {
            error_log('Paid payment repair failed for attempt ' . ($attempt['id'] ?? '') . ': ' . $e->getMessage());
            db_exec_sql(
                'UPDATE payment_attempts SET allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at WHERE id = :id',
                [
                    'allocation_status' => 'failed',
                    'allocation_error' => substr($e->getMessage(), 0, 1000),
                    'updated_at' => now_sql(),
                    'id' => $attempt['id'] ?? '',
                ]
            );
            payment_audit_log('Course Assigned', 'failed', (string) ($attempt['student_id'] ?? ''), (string) ($attempt['id'] ?? ''), null, null, $e->getMessage());
        }
    }
}


function subscription_success_payment_status(string $status): bool
{
    return in_array(strtolower(trim($status)), ['paid', 'captured', 'success'], true);
}

function subscription_level_number(?string $name): ?string
{
    $name = strtolower(trim((string) $name));
    if ($name === '') {
        return null;
    }
    if (preg_match('/level\s*0|foundation/i', $name)) {
        return '0';
    }
    if (preg_match('/level\s*(\d+)/i', $name, $m) === 1) {
        return (string) ((int) $m[1]);
    }
    return null;
}

function subscription_program_type_from_text(string $text): ?string
{
    $text = strtolower($text);
    if (str_contains($text, 'vedic')) {
        return 'vedic';
    }
    if (str_contains($text, 'abacus')) {
        return 'abacus';
    }
    return null;
}

function subscription_resolve_worksheet_level(?string $levelId, string $levelName, string $planName, string $courseSlug, string $courseName): ?array
{
    if (function_exists('ensure_worksheet_sub_schema')) {
        ensure_worksheet_sub_schema();
    }

    $levelId = trim((string) $levelId);
    if ($levelId !== '') {
        $level = db_one('SELECT id, level_name FROM worksheet_levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
        if ($level) {
            return $level;
        }
    }

    $haystack = trim($courseSlug . ' ' . $courseName . ' ' . $levelName . ' ' . $planName);
    $programType = subscription_program_type_from_text($haystack);
    $levelNumber = subscription_level_number($levelName !== '' ? $levelName : $planName);
    if ($programType === null || $levelNumber === null) {
        return null;
    }

    $levels = db_all('SELECT id, level_name FROM worksheet_levels ORDER BY level_name ASC');
    foreach ($levels as $level) {
        $candidateName = (string) ($level['level_name'] ?? '');
        if (subscription_program_type_from_text($candidateName) !== $programType) {
            continue;
        }
        if (subscription_level_number($candidateName) === $levelNumber) {
            return $level;
        }
    }

    return null;
}

function subscription_is_active_window(?string $startDate, ?string $endDate, bool $lifetime = false): bool
{
    $startTs = strtotime((string) $startDate);
    if ($startTs === false) {
        return false;
    }
    if ($lifetime && ($endDate === null || trim((string) $endDate) === '')) {
        return true;
    }
    $endTs = strtotime((string) $endDate);
    return $endTs !== false && $endTs >= time();
}

function getActiveWorksheetSubscription(string $studentId): ?array
{
    ensure_billing_schema();
    if (function_exists('repair_paid_payment_attempt_subscriptions')) {
        repair_paid_payment_attempt_subscriptions($studentId);
    }

    $rows = db_all(
        'SELECT ss.*, p.duration_days, p.name AS plan_name_ref, p.level_id AS plan_level_id,
                l.level_name, l.course_id, c.name AS course_name, c.slug AS course_slug,
                pa.provider, pa.provider_order_id, pa.provider_payment_id, pa.status AS attempt_status
         FROM student_subscriptions ss
         LEFT JOIN subscription_plans p ON p.id = ss.plan_id
         LEFT JOIN levels l ON l.id = COALESCE(ss.level_id, p.level_id)
         LEFT JOIN courses c ON c.id = l.course_id
         LEFT JOIN payment_attempts pa ON pa.id = ss.payment_attempt_id
         WHERE ss.student_id = :student_id
         ORDER BY ss.expiry_date DESC, ss.updated_at DESC, ss.created_at DESC',
        ['student_id' => $studentId]
    );

    $now = now_sql();
    foreach ($rows as $row) {
        $paymentStatus = strtolower((string) ($row['payment_status'] ?? ''));
        $subscriptionStatus = strtolower((string) ($row['status'] ?? ''));
        $planName = (string) ($row['plan_name_ref'] ?? $row['plan_name'] ?? '');
        $levelName = (string) ($row['level_name'] ?? '');
        $courseSlug = (string) ($row['course_slug'] ?? '');
        $courseName = (string) ($row['course_name'] ?? '');
        $haystack = strtolower($courseSlug . ' ' . $courseName . ' ' . $levelName . ' ' . $planName . ' ' . (string) ($row['plan_name'] ?? ''));
        if (!str_contains($haystack, 'worksheet') || (!str_contains($haystack, 'abacus') && !str_contains($haystack, 'vedic'))) {
            continue;
        }

        $level = subscription_resolve_worksheet_level($row['level_id'] ?: ($row['plan_level_id'] ?? null), $levelName, $planName, $courseSlug, $courseName);
        $isLifetime = (int) ($row['duration_days'] ?? 0) <= 0;
        $isActive = $subscriptionStatus === 'active'
            && subscription_success_payment_status($paymentStatus)
            && subscription_is_active_window($row['start_date'] ?? null, $row['expiry_date'] ?? null, $isLifetime)
            && $level !== null;

        if (!$isActive) {
            payment_audit_log(
                'Active Subscription Resolver',
                'warning',
                $studentId,
                $row['payment_attempt_id'] ?? null,
                $row['id'] ?? null,
                $row['level_id'] ?? null,
                'Worksheet subscription row was not active/resolvable.',
                [
                    'subscriptionStatus' => $subscriptionStatus,
                    'paymentStatus' => $paymentStatus,
                    'startDate' => $row['start_date'] ?? null,
                    'endDate' => $row['expiry_date'] ?? null,
                    'planName' => $planName,
                    'levelName' => $levelName,
                    'courseSlug' => $courseSlug,
                    'resolvedLevel' => $level,
                ]
            );
            continue;
        }

        $programType = subscription_program_type_from_text((string) ($level['level_name'] ?? '') . ' ' . $courseSlug . ' ' . $courseName) ?: 'abacus';
        if ((string) ($row['level_id'] ?? '') !== (string) ($level['id'] ?? '')) {
            db_exec_sql(
                'UPDATE student_subscriptions SET level_id = :level_id, updated_at = :updated_at WHERE id = :id',
                ['level_id' => $level['id'], 'updated_at' => $now, 'id' => $row['id']]
            );
        }

        return [
            'subscription_id' => $row['id'],
            'id' => $row['id'],
            'user_id' => $studentId,
            'student_id' => $studentId,
            'product_id' => $row['course_id'] ?? null,
            'plan_id' => $row['plan_id'] ?? null,
            'planId' => $row['plan_id'] ?? null,
            'program_type' => $programType,
            'level_id' => $level['id'] ?? null,
            'levelId' => $level['id'] ?? null,
            'level_name' => $level['level_name'] ?? $levelName,
            'levelName' => $level['level_name'] ?? $levelName,
            'plan_name' => $planName !== '' ? $planName : (string) ($row['plan_name'] ?? ''),
            'planName' => $planName !== '' ? $planName : (string) ($row['plan_name'] ?? ''),
            'amount' => (float) ($row['amount'] ?? 0),
            'currency' => $row['currency'] ?? 'INR',
            'subscription_status' => $subscriptionStatus,
            'status' => $subscriptionStatus,
            'payment_status' => $paymentStatus,
            'paymentStatus' => subscription_success_payment_status($paymentStatus) ? 'paid' : 'unpaid',
            'start_date' => $row['start_date'] ?? null,
            'startDate' => $row['start_date'] ?? null,
            'end_date' => $row['expiry_date'] ?? null,
            'expiryDate' => $row['expiry_date'] ?? null,
            'is_active' => true,
            'payment_id' => $row['razorpay_payment_id'] ?? ($row['provider_payment_id'] ?? null),
            'razorpayPaymentId' => $row['razorpay_payment_id'] ?? ($row['provider_payment_id'] ?? null),
            'order_id' => $row['razorpay_order_id'] ?? ($row['provider_order_id'] ?? null),
            'razorpayOrderId' => $row['razorpay_order_id'] ?? ($row['provider_order_id'] ?? null),
            'gateway' => $row['provider'] ?? 'razorpay',
            'is_test_payment' => strtolower((string) ($row['provider'] ?? 'razorpay')) === 'mock',
        ];
    }

    return null;
}
function get_student_subscription_overview(string $studentId): array
{
    repair_paid_payment_attempt_subscriptions($studentId);
    $current = sync_student_subscription_state($studentId);
    repair_student_course_enrollments($studentId);

    $activeWorksheet = getActiveWorksheetSubscription($studentId);

    $historyRows = db_all(
        'SELECT ss.*, l.level_name
         FROM student_subscriptions ss
         LEFT JOIN levels l ON l.id = ss.level_id
         WHERE ss.student_id = :student_id
         ORDER BY ss.created_at DESC
         LIMIT 20',
        ['student_id' => $studentId]
    );

    return [
        'current' => $activeWorksheet ?: ($current ? map_subscription_row($current) : null),
        'history' => array_map(static fn(array $row): array => map_subscription_row($row), $historyRows),
        'activeWorksheet' => $activeWorksheet,
    ];
}

function get_student_courses_for_dashboard(string $studentId): array
{
    repair_student_course_enrollments($studentId);

    $rows = db_all(
        'SELECT sc.*, ss.plan_name, ss.amount, ss.currency, l.level_name, c.name AS course_name, c.slug AS course_slug
         FROM student_courses sc
         LEFT JOIN student_subscriptions ss ON ss.id = sc.subscription_id
         LEFT JOIN levels l ON l.id = sc.level_id
         LEFT JOIN courses c ON c.id = sc.course_id
         WHERE sc.student_id = :student_id AND sc.status = "active" AND sc.access_end >= :now_ts
         ORDER BY sc.access_end DESC, sc.created_at DESC',
        ['student_id' => $studentId, 'now_ts' => now_sql()]
    );

    return array_map(static fn(array $row): array => [
        'id' => $row['id'],
        'courseId' => $row['course_id'] ?? null,
        'courseName' => $row['course_name'] ?? 'Course',
        'courseSlug' => $row['course_slug'] ?? null,
        'levelId' => $row['level_id'] ?? null,
        'levelName' => $row['level_name'] ?? null,
        'subscriptionId' => $row['subscription_id'],
        'planName' => $row['plan_name'] ?? '',
        'amount' => (float) ($row['amount'] ?? 0),
        'currency' => $row['currency'] ?? 'INR',
        'status' => $row['status'] ?? 'active',
        'accessStart' => $row['access_start'] ?? null,
        'accessEnd' => $row['access_end'] ?? null,
    ], $rows);
}

function controller_student_courses(array $ctx): void
{
    ensure_billing_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    json_response(['courses' => get_student_courses_for_dashboard((string) $student['id'])]);
}

function subscription_plan_rows_for_shop(): array
{
    try {
        return db_all(
            'SELECT p.*, l.level_name, l.course_id, c.name AS course_name, c.slug AS course_slug
             FROM subscription_plans p
             LEFT JOIN levels l ON l.id = p.level_id
             LEFT JOIN courses c ON c.id = l.course_id
             WHERE p.is_active = 1
             ORDER BY COALESCE(c.name, p.name), COALESCE(l.level_name, p.name), p.price ASC'
        );
    } catch (Throwable $e) {
        error_log('[ShopAPI] subscription plan relation query failed: ' . $e->getMessage());
        return db_all(
            'SELECT p.*, NULL AS level_name, NULL AS course_id, NULL AS course_name, NULL AS course_slug
             FROM subscription_plans p
             WHERE p.is_active = 1
             ORDER BY p.name, p.price ASC'
        );
    }
}
function controller_student_subscription_plans(array $ctx): void
{
    ensure_billing_schema();
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $rows = subscription_plan_rows_for_shop();

    $plans = array_map(static function (array $row): array {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'levelId' => $row['level_id'] ?? null,
            'levelName' => $row['level_name'] ?? null,
            'courseId' => $row['course_id'] ?? null,
            'courseName' => $row['course_name'] ?? null,
            'courseSlug' => $row['course_slug'] ?? null,
            'durationDays' => (int) ($row['duration_days'] ?? 0),
            'price' => (float) ($row['price'] ?? 0),
            'currency' => $row['currency'] ?? 'INR',
            'isActive' => ((int) ($row['is_active'] ?? 1)) === 1,
        ];
    }, $rows);

    json_response(['plans' => $plans]);
}

function controller_public_subscription_plans(): void
{
    ensure_billing_schema();

    $rows = subscription_plan_rows_for_shop();

    $plans = array_map(static function (array $row): array {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'levelId' => $row['level_id'] ?? null,
            'levelName' => $row['level_name'] ?? null,
            'courseId' => $row['course_id'] ?? null,
            'courseName' => $row['course_name'] ?? null,
            'courseSlug' => $row['course_slug'] ?? null,
            'durationDays' => (int) ($row['duration_days'] ?? 0),
            'price' => (float) ($row['price'] ?? 0),
            'currency' => $row['currency'] ?? 'INR',
            'isActive' => ((int) ($row['is_active'] ?? 1)) === 1,
        ];
    }, $rows);

    json_response(['plans' => $plans]);
}

function controller_student_subscription_summary(array $ctx): void
{
    ensure_billing_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $gateway = get_payment_gateway_config('razorpay');
    try {
        $overview = get_student_subscription_overview((string) $student['id']);
    } catch (Throwable $e) {
        error_log('[ShopAPI] subscription summary failed for student=' . ($student['id'] ?? '') . ': ' . $e->getMessage());
        $overview = ['current' => null, 'history' => []];
    }

    json_response([
        'student' => [
            'id' => $student['id'],
            'name' => $student['user_name'] ?? '',
            'email' => $student['user_email'] ?? '',
            'levelId' => $student['level_id'] ?? null,
            'levelName' => $student['level_name'] ?? null,
            'courseId' => $student['course_id'] ?? null,
            'courseName' => $student['course_name'] ?? null,
        ],
        'subscription' => $overview,
        'canPay' => $gateway['configured'],
    ]);
}

function controller_student_subscriptions_me(array $ctx): void
{
    controller_student_subscription_summary($ctx);
}

function controller_student_ensure_worksheet_plan(array $ctx, array $data): void
{
    ensure_billing_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $courseSlug = trim((string) ($data['courseSlug'] ?? ''));
    $level = trim((string) ($data['level'] ?? ''));
    $durationDays = (int) ($data['durationDays'] ?? 0);

    if (!in_array($courseSlug, ['abacus-worksheet', 'vedic-maths-worksheet'], true) || $level === '' || !in_array($durationDays, [90, 365], true)) {
        json_response(['message' => 'Invalid worksheet plan request'], 422);
    }

    $plan = ensure_single_worksheet_subscription_plan($courseSlug, $level, $durationDays);
    if (!$plan) {
        json_response(['message' => 'Unable to create worksheet plan'], 500);
    }

    $row = db_one(
        'SELECT p.*, l.level_name, l.course_id, c.name AS course_name, c.slug AS course_slug
         FROM subscription_plans p
         LEFT JOIN levels l ON l.id = p.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         WHERE p.id = :id LIMIT 1',
        ['id' => $plan['id']]
    );

    json_response(['plan' => [
        'id' => $row['id'],
        'name' => $row['name'],
        'levelId' => $row['level_id'] ?? null,
        'levelName' => $row['level_name'] ?? null,
        'courseId' => $row['course_id'] ?? null,
        'courseName' => $row['course_name'] ?? null,
        'courseSlug' => $row['course_slug'] ?? null,
        'durationDays' => (int) ($row['duration_days'] ?? 0),
        'price' => (float) ($row['price'] ?? 0),
        'currency' => $row['currency'] ?? 'INR',
        'isActive' => ((int) ($row['is_active'] ?? 1)) === 1,
    ]]);
}

function controller_student_worksheets_list(array $ctx): void
{
    controller_student_worksheets($ctx);
}

function controller_student_worksheet_download(array $ctx, string $worksheetId): void
{
    $student = current_student($ctx['user']['id']);
    if (!$student || empty($student['level_id'])) {
        json_response(['message' => 'Level not assigned'], 404);
    }
    require_active_subscription($ctx['user']['id']);

    $worksheet = db_one('SELECT * FROM worksheets WHERE id = :id LIMIT 1', ['id' => $worksheetId]);
    if (!$worksheet) {
        json_response(['message' => 'Worksheet not found'], 404);
    }
    if ($worksheet['level_id'] !== $student['level_id']) {
        json_response(['message' => 'Access denied for this worksheet'], 403);
    }

    $pdfUrl = trim((string) ($worksheet['pdf_url'] ?? ''));
    if ($pdfUrl === '') {
        json_response(['message' => 'Worksheet file not available'], 404);
    }

    if (str_starts_with($pdfUrl, 'http://') || str_starts_with($pdfUrl, 'https://')) {
        $url = $pdfUrl;
    } else {
        $base = get_base_url();
        $url = rtrim($base, '/') . '/' . ltrim($pdfUrl, '/');
    }

    json_response(['url' => $url]);
}

function controller_student_create_razorpay_order(array $ctx, array $data): void
{
    ensure_billing_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $planIds = [];
    if (isset($data['planIds']) && is_array($data['planIds'])) {
        $planIds = array_values(array_unique(array_filter(array_map(static fn($id): string => trim((string) $id), $data['planIds']))));
    } else {
        $planId = trim((string) ($data['planId'] ?? ''));
        if ($planId !== '') {
            $planIds = [$planId];
        }
    }
    if (!$planIds) {
        json_response(['message' => 'planId is required'], 422);
    }

    $placeholders = implode(',', array_fill(0, count($planIds), '?'));
    $plans = db_all(
        "SELECT p.*, l.level_name
         FROM subscription_plans p
         LEFT JOIN levels l ON l.id = p.level_id
         WHERE p.id IN ({$placeholders}) AND p.is_active = 1",
        $planIds
    );
    if (count($plans) !== count($planIds)) {
        json_response(['message' => 'Subscription plan not found'], 404);
    }

    $gateway = get_payment_gateway_config('razorpay');
    if (!$gateway['configured']) {
        json_response(['message' => 'Razorpay is not configured. Please contact admin.'], 400);
    }

    $amount = array_reduce($plans, static fn(float $sum, array $plan): float => $sum + (float) ($plan['price'] ?? 0), 0.0);
    if ($amount <= 0) {
        json_response(['message' => 'Invalid plan price'], 422);
    }
    $currency = (string) ($plans[0]['currency'] ?? 'INR');
    $amountPaise = (int) round($amount * 100);

    $receipt = 'sub_' . substr(str_replace('-', '', $student['id']), 0, 10) . '_' . time();
    $orderPayload = [
        'amount' => $amountPaise,
        'currency' => $currency,
        'receipt' => $receipt,
        'notes' => [
            'student_id' => $student['id'],
            'plan_id' => $plans[0]['id'],
            'plan_name' => count($plans) === 1 ? $plans[0]['name'] : count($plans) . ' worksheet levels',
            'plan_ids' => implode(',', $planIds),
        ],
    ];

    payment_audit_log(
        'Payment Initiated',
        'info',
        (string) $student['id'],
        null,
        null,
        $plans[0]['level_id'] ?? null,
        'Student initiated worksheet subscription payment.',
        ['planIds' => $planIds, 'amount' => $amount, 'currency' => $currency]
    );

    $orderResp = razorpay_request('POST', '/orders', $orderPayload, $gateway['key_id'], $gateway['secret']);
    if (!($orderResp['ok'] ?? false)) {
        payment_audit_log(
            'Order Created',
            'failed',
            (string) $student['id'],
            null,
            null,
            $plans[0]['level_id'] ?? null,
            (string) ($orderResp['message'] ?? 'Failed to create payment order'),
            ['planIds' => $planIds]
        );
        json_response(['message' => (string) ($orderResp['message'] ?? 'Failed to create payment order')], 502);
    }

    $rzOrder = (array) ($orderResp['data'] ?? []);
    $attemptId = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO payment_attempts
         (id, student_id, plan_id, provider, amount, currency, status, allocation_status, provider_order_id, metadata_json, created_at, updated_at)
         VALUES
         (:id, :student_id, :plan_id, :provider, :amount, :currency, :status, :allocation_status, :provider_order_id, :metadata_json, :created_at, :updated_at)',
        [
            'id' => $attemptId,
            'student_id' => $student['id'],
            'plan_id' => $plans[0]['id'],
            'provider' => 'razorpay',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'created',
            'allocation_status' => 'pending',
            'provider_order_id' => $rzOrder['id'] ?? null,
            'metadata_json' => json_encode(['order' => $rzOrder, 'plan_ids' => $planIds], JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    payment_audit_log(
        'Order Created',
        'success',
        (string) $student['id'],
        $attemptId,
        null,
        $plans[0]['level_id'] ?? null,
        'Razorpay order created and linked to student.',
        ['razorpayOrderId' => $rzOrder['id'] ?? null, 'planIds' => $planIds, 'amount' => $amount, 'currency' => $currency]
    );

    json_response([
        'attemptId' => $attemptId,
        'keyId' => $gateway['key_id'],
        'order' => [
            'id' => $rzOrder['id'] ?? null,
            'amount' => (int) ($rzOrder['amount'] ?? $amountPaise),
            'currency' => $rzOrder['currency'] ?? $currency,
        ],
        'plan' => [
            'id' => $plans[0]['id'],
            'name' => count($plans) === 1 ? $plans[0]['name'] : count($plans) . ' worksheet levels',
            'levelId' => $plans[0]['level_id'] ?? null,
            'levelName' => $plans[0]['level_name'] ?? null,
            'durationDays' => (int) ($plans[0]['duration_days'] ?? 0),
            'price' => $amount,
            'currency' => $currency,
        ],
        'plans' => array_map(static fn(array $plan): array => [
            'id' => $plan['id'],
            'name' => $plan['name'],
            'levelId' => $plan['level_id'] ?? null,
            'levelName' => $plan['level_name'] ?? null,
            'durationDays' => (int) ($plan['duration_days'] ?? 0),
            'price' => (float) ($plan['price'] ?? 0),
            'currency' => (string) ($plan['currency'] ?? 'INR'),
        ], $plans),
    ]);
}

function finalize_paid_payment_attempt(array $attempt, string $paymentId, string $signature = '', ?array &$activationResult = null): array
{
    ensure_billing_schema();
    $activationResult = ['status' => 'activated', 'message' => 'Subscription activated'];

    $attemptId = (string) ($attempt['id'] ?? '');
    $studentId = (string) ($attempt['student_id'] ?? '');
    $orderId = (string) ($attempt['provider_order_id'] ?? '');
    $gateway = (string) ($attempt['provider'] ?? 'razorpay');
    $now = now_sql();

    if ($attemptId === '' || $studentId === '' || $orderId === '') {
        $activationResult = [
            'status' => 'pending_manual_review',
            'message' => 'Payment is captured, but required activation identifiers are missing.',
            'allocationStatus' => 'failed',
            'allocationError' => 'Missing payment_attempt_id, student_id, or order_id.',
        ];
        return $studentId !== '' ? get_student_subscription_overview($studentId) : ['current' => null, 'history' => []];
    }

    $planIds = paid_attempt_plan_ids($attempt);
    if (!$planIds) {
        db_exec_sql(
            'UPDATE payment_attempts SET status = :status, allocation_status = :allocation_status, allocation_error = :allocation_error, provider_payment_id = :payment_id, provider_signature = :signature, paid_at = COALESCE(paid_at, :paid_at), updated_at = :updated_at WHERE id = :id',
            [
                'status' => 'paid',
                'allocation_status' => 'failed',
                'allocation_error' => 'Captured payment has no plan ids for subscription activation.',
                'payment_id' => $paymentId,
                'signature' => $signature !== '' ? $signature : ($attempt['provider_signature'] ?? null),
                'paid_at' => $now,
                'updated_at' => $now,
                'id' => $attemptId,
            ]
        );
        payment_audit_log('Shared Subscription Activation', 'failed', $studentId, $attemptId, null, null, 'Captured payment has no plan ids for subscription activation.');
        $activationResult = [
            'status' => 'pending_manual_review',
            'message' => 'Payment is captured, but subscription activation is pending manual review.',
            'allocationStatus' => 'failed',
            'allocationError' => 'Captured payment has no plan ids for subscription activation.',
        ];
        return get_student_subscription_overview($studentId);
    }

    $placeholders = implode(',', array_fill(0, count($planIds), '?'));
    $plans = db_all("SELECT * FROM subscription_plans WHERE id IN ({$placeholders})", $planIds);
    if (count($plans) !== count($planIds)) {
        db_exec_sql(
            'UPDATE payment_attempts SET status = :status, allocation_status = :allocation_status, allocation_error = :allocation_error, provider_payment_id = :payment_id, provider_signature = :signature, paid_at = COALESCE(paid_at, :paid_at), updated_at = :updated_at WHERE id = :id',
            [
                'status' => 'paid',
                'allocation_status' => 'failed',
                'allocation_error' => 'Captured payment references missing subscription plans.',
                'payment_id' => $paymentId,
                'signature' => $signature !== '' ? $signature : ($attempt['provider_signature'] ?? null),
                'paid_at' => $now,
                'updated_at' => $now,
                'id' => $attemptId,
            ]
        );
        payment_audit_log('Shared Subscription Activation', 'failed', $studentId, $attemptId, null, null, 'Captured payment references missing subscription plans.', ['planIds' => $planIds]);
        $activationResult = [
            'status' => 'pending_manual_review',
            'message' => 'Payment is captured, but subscription activation is pending manual review.',
            'allocationStatus' => 'failed',
            'allocationError' => 'Captured payment references missing subscription plans.',
        ];
        return get_student_subscription_overview($studentId);
    }

    foreach ($plans as $plan) {
        if ((int) ($plan['duration_days'] ?? 0) <= 0) {
            $activationResult = [
                'status' => 'pending_manual_review',
                'message' => 'Payment is captured, but subscription activation is pending manual review.',
                'allocationStatus' => 'failed',
                'allocationError' => 'Invalid plan duration for ' . ($plan['name'] ?? $plan['id']),
            ];
            payment_audit_log('Shared Subscription Activation', 'failed', $studentId, $attemptId, null, $plan['level_id'] ?? null, $activationResult['allocationError'], ['planId' => $plan['id'] ?? null]);
            return get_student_subscription_overview($studentId);
        }
    }

    $primaryPlan = $plans[0];
    try {
        payment_audit_log(
            'Payment Success',
            'success',
            $studentId,
            $attemptId,
            null,
            $primaryPlan['level_id'] ?? null,
            'Payment signature/webhook verified successfully; activating through shared subscription service.',
            ['razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'planIds' => $planIds]
        );

        $overview = activateWorksheetSubscription([
            'userId' => $studentId,
            'productId' => $primaryPlan['course_id'] ?? null,
            'planId' => $primaryPlan['id'],
            'programType' => stripos((string) ($primaryPlan['name'] ?? ''), 'vedic') !== false ? 'vedic' : 'abacus',
            'levelId' => $primaryPlan['level_id'] ?? null,
            'paymentId' => $paymentId,
            'orderId' => $orderId,
            'paymentStatus' => 'captured',
            'gateway' => $gateway !== '' ? $gateway : 'razorpay',
            'startDate' => $attempt['paid_at'] ?? $now,
            'endDate' => null,
            'paymentAttemptId' => $attemptId,
            'signature' => $signature,
        ]);
    } catch (Throwable $e) {
        error_log('Payment activation failed for attempt ' . $attemptId . ': ' . $e->getMessage());
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, allocation_status = :allocation_status, allocation_error = :allocation_error,
                 provider_payment_id = :payment_id, provider_signature = :signature, paid_at = COALESCE(paid_at, :paid_at), updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'paid',
                'allocation_status' => 'failed',
                'allocation_error' => substr($e->getMessage(), 0, 1000),
                'payment_id' => $paymentId,
                'signature' => $signature !== '' ? $signature : ($attempt['provider_signature'] ?? null),
                'paid_at' => $now,
                'updated_at' => now_sql(),
                'id' => $attemptId,
            ]
        );
        payment_audit_log(
            'Shared Subscription Activation',
            'failed',
            $studentId,
            $attemptId,
            null,
            $primaryPlan['level_id'] ?? null,
            $e->getMessage(),
            ['razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'planIds' => $planIds]
        );
        $activationResult = [
            'status' => 'pending_manual_review',
            'message' => 'Payment is captured, but subscription activation is pending manual review.',
            'allocationStatus' => 'failed',
            'allocationError' => $e->getMessage(),
        ];
        return get_student_subscription_overview($studentId);
    }

    $freshAttempt = db_one('SELECT allocation_status, allocation_error FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attemptId]);
    if (($freshAttempt['allocation_status'] ?? '') !== 'assigned') {
        $activationResult = [
            'status' => 'pending_manual_review',
            'message' => 'Payment is captured, but subscription activation is pending manual review.',
            'allocationStatus' => $freshAttempt['allocation_status'] ?? null,
            'allocationError' => $freshAttempt['allocation_error'] ?? null,
        ];
        return $overview;
    }

    $activationResult = ['status' => 'activated', 'message' => 'Subscription activated', 'allocationStatus' => 'assigned'];
    return $overview;
}
function controller_student_verify_razorpay_payment(array $ctx, array $data): void
{
    ensure_billing_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $attemptId = trim((string) ($data['attemptId'] ?? ''));
    $orderId = trim((string) ($data['razorpayOrderId'] ?? ''));
    $paymentId = trim((string) ($data['razorpayPaymentId'] ?? ''));
    $signature = trim((string) ($data['razorpaySignature'] ?? ''));

    if ($attemptId === '' || $orderId === '' || $paymentId === '' || $signature === '') {
        json_response(['message' => 'Invalid payment verification payload'], 422);
    }

    $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id AND student_id = :student_id LIMIT 1', [
        'id' => $attemptId,
        'student_id' => $student['id'],
    ]);
    if (!$attempt) {
        json_response(['message' => 'Payment attempt not found'], 404);
    }

    record_payment_gateway_payload($attempt, 'checkout_success', [
        'razorpayOrderId' => $orderId,
        'razorpayPaymentId' => $paymentId,
        'razorpaySignature' => $signature,
        'receivedAt' => gmdate('c'),
    ]);
    $attempt['metadata_json'] = json_encode(payment_attempt_metadata($attempt) + ['checkout_success' => [
        'razorpayOrderId' => $orderId,
        'razorpayPaymentId' => $paymentId,
        'razorpaySignature' => $signature,
        'receivedAt' => gmdate('c'),
    ]], JSON_UNESCAPED_SLASHES);

    if (($attempt['provider_order_id'] ?? '') !== $orderId) {
        payment_audit_log(
            'Payment Verification',
            'failed',
            (string) $student['id'],
            $attemptId,
            null,
            null,
            'Payment order mismatch during checkout verification.',
            ['expectedOrderId' => $attempt['provider_order_id'] ?? null, 'receivedOrderId' => $orderId, 'razorpayPaymentId' => $paymentId]
        );
        json_response(['message' => 'Payment order mismatch'], 422);
    }

    $gateway = get_payment_gateway_config('razorpay');
    if ($gateway['secret'] === '') {
        payment_audit_log(
            'Payment Verification',
            'failed',
            (string) $student['id'],
            $attemptId,
            null,
            null,
            'Razorpay secret is not configured for payment verification.',
            ['keyIdConfigured' => ($gateway['key_id'] ?? '') !== '']
        );
        json_response(['message' => 'Razorpay secret is not configured'], 500);
    }

    $paymentResp = fetch_razorpay_payment_status($paymentId, $gateway);
    $paymentEntity = (array) ($paymentResp['data'] ?? []);
    if ($paymentResp['ok'] ?? false) {
        record_payment_gateway_payload($attempt, 'razorpay_payment', $paymentEntity);
        $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attemptId]) ?: $attempt;
    } else {
        payment_audit_log(
            'Payment Verification',
            'warning',
            (string) $student['id'],
            $attemptId,
            null,
            null,
            (string) ($paymentResp['message'] ?? 'Unable to fetch Razorpay payment status.'),
            ['razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId]
        );
    }

    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $gateway['secret']);
    $signatureMatches = hash_equals($expected, $signature);
    $gatewayOrderId = trim((string) ($paymentEntity['order_id'] ?? ''));
    $gatewayStatus = strtolower(trim((string) ($paymentEntity['status'] ?? '')));
    $isCaptured = ($paymentResp['ok'] ?? false) && $gatewayOrderId === $orderId && $gatewayStatus === 'captured';

    if (!$signatureMatches && !$isCaptured) {
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, provider_payment_id = :payment_id, provider_signature = :signature, allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'failed',
                'payment_id' => $paymentId,
                'signature' => $signature,
                'allocation_status' => 'failed',
                'allocation_error' => 'Payment signature verification failed and Razorpay captured status was not confirmed.',
                'updated_at' => now_sql(),
                'id' => $attemptId,
            ]
        );
        payment_audit_log(
            'Payment Verification',
            'failed',
            (string) $student['id'],
            $attemptId,
            null,
            null,
            'Payment signature verification failed.',
            ['razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'gatewayStatus' => $gatewayStatus ?: null]
        );
        json_response(['message' => 'Payment signature verification failed. Please contact support with your payment ID.'], 422);
    }

    if (!$signatureMatches && $isCaptured) {
        payment_audit_log(
            'Payment Verification',
            'warning',
            (string) $student['id'],
            $attemptId,
            null,
            null,
            'Signature mismatch, but Razorpay confirms the payment is captured for this order.',
            ['razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'gatewayStatus' => $gatewayStatus]
        );
    }

    if (($paymentResp['ok'] ?? false) && $gatewayOrderId !== $orderId) {
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, provider_payment_id = :payment_id, provider_signature = :signature, allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'failed',
                'payment_id' => $paymentId,
                'signature' => $signature,
                'allocation_status' => 'failed',
                'allocation_error' => 'Razorpay payment belongs to a different order.',
                'updated_at' => now_sql(),
                'id' => $attemptId,
            ]
        );
        payment_audit_log(
            'Payment Verification',
            'failed',
            (string) $student['id'],
            $attemptId,
            null,
            null,
            'Razorpay payment order_id does not match the local order.',
            ['localOrderId' => $orderId, 'gatewayOrderId' => $gatewayOrderId, 'razorpayPaymentId' => $paymentId]
        );
        json_response(['message' => 'Payment order mismatch. Please contact support with your payment ID.'], 422);
    }

    if (($paymentResp['ok'] ?? false) && $gatewayStatus !== 'captured') {
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, provider_payment_id = :payment_id, provider_signature = :signature, allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'pending',
                'payment_id' => $paymentId,
                'signature' => $signature,
                'allocation_status' => 'pending_manual_review',
                'allocation_error' => 'Razorpay payment status is ' . ($gatewayStatus !== '' ? $gatewayStatus : 'unknown') . ', not captured.',
                'updated_at' => now_sql(),
                'id' => $attemptId,
            ]
        );
        payment_audit_log(
            'Payment Verification',
            'warning',
            (string) $student['id'],
            $attemptId,
            null,
            null,
            'Payment verified but Razorpay has not marked it captured yet.',
            ['razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'gatewayStatus' => $gatewayStatus]
        );
        json_response([
            'message' => 'Payment is not captured yet. We saved it for manual review.',
            'activationStatus' => 'pending_manual_review',
            'subscription' => get_student_subscription_overview((string) $student['id']),
        ], 202);
    }

    $activation = null;
    $overview = finalize_paid_payment_attempt($attempt, $paymentId, $signature, $activation);
    $message = (string) ($activation['message'] ?? 'Subscription activated');
    $statusCode = (($activation['status'] ?? 'activated') === 'activated') ? 200 : 202;

    json_response([
        'message' => $message,
        'activationStatus' => $activation['status'] ?? 'activated',
        'allocationStatus' => $activation['allocationStatus'] ?? 'assigned',
        'allocationError' => $activation['allocationError'] ?? null,
        'paymentStatus' => 'captured',
        'subscription' => $overview,
    ], $statusCode);
}

function handle_payment_webhook(array $data): array
{
    ensure_billing_schema();

    $provider = strtolower(trim((string) ($data['provider'] ?? 'razorpay')));
    $event = (string) ($data['event'] ?? '');
    if ($provider !== 'razorpay') {
        return ['processed' => false, 'message' => 'Unsupported payment provider'];
    }

    $rawBody = (string) ($GLOBALS['request_raw_body'] ?? '');
    $webhookSignature = (string) (request_header('X-Razorpay-Signature') ?? request_header('payment-signature') ?? '');
    $webhookSecret = (string) envv('RAZORPAY_WEBHOOK_SECRET', envv('PAYMENT_WEBHOOK_SECRET', ''));
    if ($webhookSecret !== '') {
        $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);
        if ($webhookSignature === '' || !hash_equals($expectedSignature, $webhookSignature)) {
            error_log('Razorpay webhook signature verification failed for event ' . $event);
            json_response(['message' => 'Invalid webhook signature'], 401);
        }
    }

    $trackedEvents = ['payment.captured', 'payment.authorized', 'order.paid'];
    if (!in_array($event, $trackedEvents, true)) {
        return ['processed' => false, 'message' => 'Webhook ignored'];
    }

    $payment = is_array($data['payload']['payment']['entity'] ?? null) ? $data['payload']['payment']['entity'] : [];
    $order = is_array($data['payload']['order']['entity'] ?? null) ? $data['payload']['order']['entity'] : [];
    $orderId = trim((string) ($payment['order_id'] ?? $order['id'] ?? $data['razorpayOrderId'] ?? ''));
    $paymentId = trim((string) ($payment['id'] ?? $data['razorpayPaymentId'] ?? ''));
    $paymentStatus = strtolower(trim((string) ($payment['status'] ?? ($event === 'order.paid' ? 'captured' : ''))));

    if ($orderId === '' || $paymentId === '') {
        error_log('Razorpay webhook missing order/payment id: ' . json_encode(['event' => $event], JSON_UNESCAPED_SLASHES));
        payment_audit_log(
            'Webhook Received',
            'failed',
            null,
            null,
            null,
            null,
            'Razorpay webhook missing order/payment id.',
            ['event' => $event, 'paymentStatus' => $paymentStatus ?: null]
        );
        return ['processed' => false, 'message' => 'Webhook missing order/payment id'];
    }

    $attempt = db_one(
        'SELECT * FROM payment_attempts WHERE provider = "razorpay" AND provider_order_id = :order_id LIMIT 1',
        ['order_id' => $orderId]
    );
    if (!$attempt) {
        error_log('Razorpay webhook payment attempt not found for order ' . $orderId);
        payment_audit_log(
            'Webhook Received',
            'failed',
            null,
            null,
            null,
            null,
            'Razorpay webhook payment attempt not found.',
            ['event' => $event, 'razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'paymentStatus' => $paymentStatus ?: null]
        );
        return ['processed' => false, 'message' => 'Payment attempt not found'];
    }

    record_payment_gateway_payload($attempt, 'webhook_' . str_replace('.', '_', $event), [
        'event' => $event,
        'razorpayOrderId' => $orderId,
        'razorpayPaymentId' => $paymentId,
        'webhookSignature' => $webhookSignature !== '' ? $webhookSignature : null,
        'paymentStatus' => $paymentStatus ?: null,
        'receivedAt' => gmdate('c'),
        'payload' => $data['payload'] ?? $data,
    ]);

    payment_audit_log(
        'Webhook Received',
        'info',
        (string) ($attempt['student_id'] ?? ''),
        (string) ($attempt['id'] ?? ''),
        null,
        null,
        'Razorpay webhook received.',
        ['event' => $event, 'razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'paymentStatus' => $paymentStatus ?: null]
    );

    if ($paymentStatus !== 'captured') {
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, provider_payment_id = :payment_id, allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'pending',
                'payment_id' => $paymentId,
                'allocation_status' => 'pending_manual_review',
                'allocation_error' => 'Webhook received but Razorpay status is ' . ($paymentStatus !== '' ? $paymentStatus : 'unknown') . ', not captured.',
                'updated_at' => now_sql(),
                'id' => $attempt['id'],
            ]
        );
        payment_audit_log(
            'Webhook Payment Status',
            'warning',
            (string) ($attempt['student_id'] ?? ''),
            (string) ($attempt['id'] ?? ''),
            null,
            null,
            'Webhook payment is not captured yet; kept pending for manual review.',
            ['event' => $event, 'razorpayOrderId' => $orderId, 'razorpayPaymentId' => $paymentId, 'paymentStatus' => $paymentStatus ?: null]
        );
        return [
            'processed' => true,
            'message' => 'Payment is not captured yet; kept pending for manual review',
            'studentId' => $attempt['student_id'] ?? null,
            'activationStatus' => 'pending_manual_review',
        ];
    }

    $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attempt['id']]) ?: $attempt;
    $activation = null;
    $overview = finalize_paid_payment_attempt($attempt, $paymentId, '', $activation);

    return [
        'processed' => true,
        'message' => $activation['message'] ?? 'Payment processed',
        'studentId' => $attempt['student_id'] ?? null,
        'activationStatus' => $activation['status'] ?? 'activated',
        'allocationStatus' => $activation['allocationStatus'] ?? 'assigned',
        'allocationError' => $activation['allocationError'] ?? null,
        'subscription' => $overview,
    ];
}
function controller_admin_activate_payment_attempt(array $ctx, string $attemptId, array $data): void
{
    ensure_billing_schema();

    $attemptId = trim($attemptId);
    if ($attemptId === '') {
        json_response(['message' => 'Payment attempt id is required'], 422);
    }

    $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attemptId]);
    if (!$attempt) {
        json_response(['message' => 'Payment attempt not found'], 404);
    }
    if (($attempt['provider'] ?? '') !== 'razorpay') {
        json_response(['message' => 'Only Razorpay payment attempts can be manually activated here'], 422);
    }

    $paymentId = trim((string) ($data['razorpayPaymentId'] ?? $data['paymentId'] ?? $attempt['provider_payment_id'] ?? ''));
    $signature = trim((string) ($data['razorpaySignature'] ?? $attempt['provider_signature'] ?? ''));
    if ($paymentId === '') {
        json_response(['message' => 'Razorpay payment id is required for manual activation'], 422);
    }

    $gateway = get_payment_gateway_config('razorpay');
    if ($gateway['secret'] === '') {
        json_response(['message' => 'Razorpay secret is not configured'], 500);
    }

    $paymentResp = fetch_razorpay_payment_status($paymentId, $gateway);
    if (!($paymentResp['ok'] ?? false)) {
        payment_audit_log(
            'Manual Activation',
            'failed',
            (string) ($attempt['student_id'] ?? ''),
            $attemptId,
            null,
            null,
            (string) ($paymentResp['message'] ?? 'Unable to fetch Razorpay payment status.'),
            ['razorpayPaymentId' => $paymentId, 'adminUserId' => $ctx['user']['id'] ?? null]
        );
        json_response(['message' => (string) ($paymentResp['message'] ?? 'Unable to fetch Razorpay payment status')], 502);
    }

    $paymentEntity = (array) ($paymentResp['data'] ?? []);
    record_payment_gateway_payload($attempt, 'manual_activation_payment', $paymentEntity);
    $gatewayOrderId = trim((string) ($paymentEntity['order_id'] ?? ''));
    $gatewayStatus = strtolower(trim((string) ($paymentEntity['status'] ?? '')));
    $localOrderId = trim((string) ($attempt['provider_order_id'] ?? ''));

    if ($gatewayOrderId === '' || $gatewayOrderId !== $localOrderId) {
        payment_audit_log(
            'Manual Activation',
            'failed',
            (string) ($attempt['student_id'] ?? ''),
            $attemptId,
            null,
            null,
            'Razorpay payment order_id does not match the local order during manual activation.',
            ['localOrderId' => $localOrderId, 'gatewayOrderId' => $gatewayOrderId, 'razorpayPaymentId' => $paymentId, 'adminUserId' => $ctx['user']['id'] ?? null]
        );
        json_response(['message' => 'Razorpay payment belongs to a different order'], 422);
    }

    if ($gatewayStatus !== 'captured') {
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, provider_payment_id = :payment_id, provider_signature = :signature, allocation_status = :allocation_status, allocation_error = :allocation_error, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'pending',
                'payment_id' => $paymentId,
                'signature' => $signature !== '' ? $signature : null,
                'allocation_status' => 'pending_manual_review',
                'allocation_error' => 'Manual activation blocked because Razorpay status is ' . ($gatewayStatus !== '' ? $gatewayStatus : 'unknown') . ', not captured.',
                'updated_at' => now_sql(),
                'id' => $attemptId,
            ]
        );
        payment_audit_log(
            'Manual Activation',
            'warning',
            (string) ($attempt['student_id'] ?? ''),
            $attemptId,
            null,
            null,
            'Manual activation blocked because Razorpay payment is not captured.',
            ['gatewayStatus' => $gatewayStatus, 'razorpayPaymentId' => $paymentId, 'adminUserId' => $ctx['user']['id'] ?? null]
        );
        json_response(['message' => 'Payment is not captured yet. Kept pending for manual review.'], 202);
    }

    $attempt = db_one('SELECT * FROM payment_attempts WHERE id = :id LIMIT 1', ['id' => $attemptId]) ?: $attempt;
    payment_audit_log(
        'Manual Activation',
        'info',
        (string) ($attempt['student_id'] ?? ''),
        $attemptId,
        null,
        null,
        'Admin retrying subscription activation for captured Razorpay payment.',
        ['razorpayPaymentId' => $paymentId, 'adminUserId' => $ctx['user']['id'] ?? null]
    );

    $activation = null;
    $overview = finalize_paid_payment_attempt($attempt, $paymentId, $signature, $activation);
    $statusCode = (($activation['status'] ?? 'activated') === 'activated') ? 200 : 202;

    json_response([
        'message' => $activation['message'] ?? 'Subscription activated',
        'activationStatus' => $activation['status'] ?? 'activated',
        'allocationStatus' => $activation['allocationStatus'] ?? 'assigned',
        'allocationError' => $activation['allocationError'] ?? null,
        'paymentStatus' => 'captured',
        'subscription' => $overview,
    ], $statusCode);
}

function controller_admin_get_payment_config(array $ctx): void
{
    ensure_billing_schema();
    $provider = strtolower(trim((string) ($_GET['provider'] ?? 'razorpay')));
    $row = db_one('SELECT * FROM payment_gateway_configs WHERE provider = :provider LIMIT 1', ['provider' => $provider]);

    json_response([
        'provider' => $provider,
        'keyId' => $row['key_id'] ?? '',
        'hasSecret' => !empty($row['secret_enc']),
        'enabled' => isset($row['enabled']) ? ((int) $row['enabled'] === 1) : true,
        'updatedAt' => $row['updated_at'] ?? null,
    ]);
}

function controller_admin_set_payment_config(array $ctx, array $data): void
{
    ensure_billing_schema();

    $provider = strtolower(trim((string) ($data['provider'] ?? 'razorpay')));
    $keyId = trim((string) ($data['keyId'] ?? ''));
    $keySecret = (string) ($data['keySecret'] ?? '');
    $enabled = array_key_exists('enabled', $data) ? ((bool) $data['enabled']) : true;

    if ($provider === '' || $keyId === '') {
        json_response(['message' => 'provider and keyId are required'], 422);
    }

    $existing = db_one('SELECT * FROM payment_gateway_configs WHERE provider = :provider LIMIT 1', ['provider' => $provider]);
    $now = now_sql();
    $secretEnc = $keySecret !== '' ? encrypt_sensitive_value($keySecret) : ($existing['secret_enc'] ?? null);

    if ($existing) {
        db_exec_sql(
            'UPDATE payment_gateway_configs
             SET key_id = :key_id, secret_enc = :secret_enc, enabled = :enabled, updated_by = :updated_by, updated_at = :updated_at
             WHERE provider = :provider',
            [
                'key_id' => $keyId,
                'secret_enc' => $secretEnc,
                'enabled' => $enabled ? 1 : 0,
                'updated_by' => $ctx['user']['id'],
                'updated_at' => $now,
                'provider' => $provider,
            ]
        );
    } else {
        db_exec_sql(
            'INSERT INTO payment_gateway_configs
             (id, provider, key_id, secret_enc, enabled, created_by, updated_by, created_at, updated_at)
             VALUES
             (:id, :provider, :key_id, :secret_enc, :enabled, :created_by, :updated_by, :created_at, :updated_at)',
            [
                'id' => uuid_v4(),
                'provider' => $provider,
                'key_id' => $keyId,
                'secret_enc' => $secretEnc,
                'enabled' => $enabled ? 1 : 0,
                'created_by' => $ctx['user']['id'],
                'updated_by' => $ctx['user']['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    controller_admin_get_payment_config($ctx);
}

function controller_admin_subscriptions_list(): void
{
    ensure_billing_schema();

    $rows = db_all(
        'SELECT
            ss.*,
            u.name AS student_name,
            u.email AS student_email,
            l.level_name,
            c.name AS course_name,
            c.slug AS course_slug,
            p.duration_days,
            pa.status AS payment_attempt_status,
            pa.allocation_status AS allocation_status,
            pa.allocation_error AS allocation_error,
            pa.provider_order_id AS payment_order_id,
            pa.provider_payment_id AS payment_id
         FROM student_subscriptions ss
         INNER JOIN students s ON s.id = ss.student_id
         INNER JOIN users u ON u.id = s.user_id
         LEFT JOIN levels l ON l.id = ss.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         LEFT JOIN subscription_plans p ON p.id = ss.plan_id
         LEFT JOIN payment_attempts pa ON pa.id = ss.payment_attempt_id
         ORDER BY ss.created_at DESC'
    );

    $subscriptions = array_map(static function (array $row): array {
        $payload = map_subscription_row($row);
        $payload['student'] = [
            'id' => $row['student_id'],
            'name' => $row['student_name'] ?? '',
            'email' => $row['student_email'] ?? '',
        ];
        $payload['courseName'] = $row['course_name'] ?? '';
        $payload['courseSlug'] = $row['course_slug'] ?? '';
        $payload['durationDays'] = isset($row['duration_days']) ? (int) $row['duration_days'] : null;
        $payload['paymentAttemptStatus'] = $row['payment_attempt_status'] ?? null;
        $payload['allocationStatus'] = $row['allocation_status'] ?? null;
        $payload['allocationError'] = $row['allocation_error'] ?? null;
        $payload['paymentOrderId'] = $row['payment_order_id'] ?? null;
        $payload['paymentId'] = $row['payment_id'] ?? null;
        return $payload;
    }, $rows);

    json_response(['subscriptions' => $subscriptions]);
}

function controller_admin_payment_audit_logs(): void
{
    ensure_billing_schema();

    $status = trim((string) ($_GET['status'] ?? ''));
    $attemptId = trim((string) ($_GET['attemptId'] ?? ''));
    $studentId = trim((string) ($_GET['studentId'] ?? ''));
    $limit = max(1, min(200, (int) ($_GET['limit'] ?? 100)));

    $where = [];
    $params = [];
    if ($status !== '') {
        $where[] = 'pal.status = :status';
        $params['status'] = $status;
    }
    if ($attemptId !== '') {
        $where[] = 'pal.payment_attempt_id = :attempt_id';
        $params['attempt_id'] = $attemptId;
    }
    if ($studentId !== '') {
        $where[] = 'pal.student_id = :student_id';
        $params['student_id'] = $studentId;
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $rows = db_all(
        "SELECT
            pal.*,
            u.name AS student_name,
            u.email AS student_email,
            pa.status AS payment_attempt_status,
            pa.allocation_status,
            pa.allocation_error,
            pa.provider_order_id,
            pa.provider_payment_id
         FROM payment_audit_logs pal
         LEFT JOIN payment_attempts pa ON pa.id = pal.payment_attempt_id
         LEFT JOIN students s ON s.id = pal.student_id
         LEFT JOIN users u ON u.id = s.user_id
         {$whereSql}
         ORDER BY pal.created_at DESC
         LIMIT {$limit}",
        $params
    );

    json_response(['logs' => array_map(static function (array $row): array {
        $metadata = json_decode((string) ($row['metadata_json'] ?? ''), true);
        return [
            'id' => $row['id'],
            'event' => $row['event'],
            'status' => $row['status'],
            'message' => $row['message'] ?? null,
            'studentId' => $row['student_id'] ?? null,
            'studentName' => $row['student_name'] ?? null,
            'studentEmail' => $row['student_email'] ?? null,
            'paymentAttemptId' => $row['payment_attempt_id'] ?? null,
            'subscriptionId' => $row['subscription_id'] ?? null,
            'levelId' => $row['level_id'] ?? null,
            'paymentAttemptStatus' => $row['payment_attempt_status'] ?? null,
            'allocationStatus' => $row['allocation_status'] ?? null,
            'allocationError' => $row['allocation_error'] ?? null,
            'paymentOrderId' => $row['provider_order_id'] ?? null,
            'paymentId' => $row['provider_payment_id'] ?? null,
            'metadata' => is_array($metadata) ? $metadata : null,
            'createdAt' => $row['created_at'] ?? null,
        ];
    }, $rows)]);
}

function controller_admin_update_subscription(string $subscriptionId, array $data): void
{
    ensure_billing_schema();

    $sub = db_one('SELECT * FROM student_subscriptions WHERE id = :id LIMIT 1', ['id' => $subscriptionId]);
    if (!$sub) {
        json_response(['message' => 'Subscription not found'], 404);
    }

    $startDate = trim((string) ($data['startDate'] ?? ($sub['start_date'] ?? '')));
    $expiryDate = trim((string) ($data['expiryDate'] ?? ($sub['expiry_date'] ?? '')));
    $extendDays = (int) ($data['extendDays'] ?? 0);
    $status = trim((string) ($data['status'] ?? ($sub['status'] ?? 'active')));
    $paymentStatus = trim((string) ($data['paymentStatus'] ?? ($sub['payment_status'] ?? 'paid')));

    if (!in_array($status, ['active', 'expired', 'cancelled'], true)) {
        json_response(['message' => 'Invalid status'], 422);
    }
    if ($paymentStatus === 'pending') {
        $paymentStatus = 'unpaid';
    }
    if (!in_array($paymentStatus, ['paid', 'captured', 'success', 'unpaid'], true)) {
        json_response(['message' => 'Invalid paymentStatus'], 422);
    }

    $expiryTs = strtotime($expiryDate);
    if ($expiryTs === false) {
        json_response(['message' => 'Invalid expiryDate'], 422);
    }
    if ($extendDays > 0) {
        $expiryTs += ($extendDays * 86400);
    }
    $startTs = strtotime($startDate);
    if ($startTs === false) {
        $startTs = time();
    }

    $now = now_sql();
    db_exec_sql(
        'UPDATE student_subscriptions
         SET start_date = :start_date, expiry_date = :expiry_date, status = :status, payment_status = :payment_status, updated_at = :updated_at
         WHERE id = :id',
        [
            'start_date' => gmdate('Y-m-d H:i:s', $startTs),
            'expiry_date' => gmdate('Y-m-d H:i:s', $expiryTs),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'updated_at' => $now,
            'id' => $subscriptionId,
        ]
    );

    sync_student_subscription_state((string) $sub['student_id']);
    $updated = db_one('SELECT ss.*, l.level_name FROM student_subscriptions ss LEFT JOIN levels l ON l.id = ss.level_id WHERE ss.id = :id', ['id' => $subscriptionId]);
    json_response(['subscription' => $updated ? map_subscription_row($updated) : null]);
}

function send_sms_notification(string $to, string $message): bool
{
    $apiUrl = trim((string) envv('SMS_API_URL', ''));
    if ($apiUrl === '') {
        return false;
    }

    if (!function_exists('curl_init')) {
        return false;
    }

    $apiKey = trim((string) envv('SMS_API_KEY', ''));
    $ch = curl_init($apiUrl);
    if ($ch === false) {
        return false;
    }

    $headers = ['Content-Type: application/json'];
    if ($apiKey !== '') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }

    $payload = json_encode(['to' => $to, 'message' => $message], JSON_UNESCAPED_SLASHES);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $payload,
    ]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status >= 200 && $status < 300;
}

function controller_run_subscription_reminders(): void
{
    ensure_billing_schema();

    $incomingToken = trim((string) (request_header('X-Cron-Token') ?? ($_GET['token'] ?? '')));
    $cronToken = trim((string) envv('SUBSCRIPTION_REMINDER_CRON_TOKEN', ''));
    if ($cronToken !== '' && !hash_equals($cronToken, $incomingToken)) {
        json_response(['message' => 'Unauthorized'], 401);
    }

    $rows = db_all(
        'SELECT
            ss.id,
            ss.student_id,
            ss.plan_name,
            ss.expiry_date,
            ss.status,
            ss.payment_status,
            u.name AS student_name,
            u.email AS student_email
         FROM student_subscriptions ss
         INNER JOIN students s ON s.id = ss.student_id
         INNER JOIN users u ON u.id = s.user_id
         WHERE ss.status IN ("active", "expired")
           AND ss.payment_status IN ("paid", "captured", "success")
           AND ss.expiry_date >= :since_ts
           AND ss.expiry_date <= :until_ts',
        [
            'since_ts' => gmdate('Y-m-d H:i:s', time() - 86400),
            'until_ts' => gmdate('Y-m-d H:i:s', time() + (7 * 86400)),
        ]
    );

    $sent = 0;
    $skipped = 0;
    foreach ($rows as $row) {
        $expiryTs = strtotime((string) $row['expiry_date']);
        if ($expiryTs === false) {
            $skipped++;
            continue;
        }
        $daysLeft = (int) floor(($expiryTs - time()) / 86400);
        if (!in_array($daysLeft, [7, 3, 1, 0, -1], true)) {
            continue;
        }

        if ($daysLeft < 0) {
            $reminderType = 'after_expiry';
        } else {
            $reminderType = $daysLeft === 0 ? 'on_expiry' : 'before_' . $daysLeft . '_day';
        }
        $name = (string) ($row['student_name'] ?? 'Student');
        $plan = (string) ($row['plan_name'] ?? 'your plan');
        $expiryDate = gmdate('d M Y', $expiryTs);
        $message = $daysLeft < 0
            ? "Hi {$name}, your {$plan} subscription expired on {$expiryDate}. Please renew your level or upgrade to the next level to continue learning."
            : "Hi {$name}, your {$plan} subscription ends on {$expiryDate}. Please renew your level or upgrade to the next level to avoid interruption.";

        $alreadyMail = db_one(
            'SELECT id FROM subscription_reminders WHERE subscription_id = :subscription_id AND reminder_type = :reminder_type AND channel = :channel LIMIT 1',
            ['subscription_id' => $row['id'], 'reminder_type' => $reminderType, 'channel' => 'email']
        );
        if (!$alreadyMail && filter_var((string) $row['student_email'], FILTER_VALIDATE_EMAIL)) {
            send_plain_mail((string) $row['student_email'], 'Renew or Upgrade Your Level', $message);
            db_exec_sql(
                'INSERT INTO subscription_reminders
                 (id, subscription_id, student_id, reminder_type, channel, sent_to, message, sent_at, created_at)
                 VALUES
                 (:id, :subscription_id, :student_id, :reminder_type, :channel, :sent_to, :message, :sent_at, :created_at)',
                [
                    'id' => uuid_v4(),
                    'subscription_id' => $row['id'],
                    'student_id' => $row['student_id'],
                    'reminder_type' => $reminderType,
                    'channel' => 'email',
                    'sent_to' => $row['student_email'],
                    'message' => $message,
                    'sent_at' => now_sql(),
                    'created_at' => now_sql(),
                ]
            );
            $sent++;
        }

        $smsTo = trim((string) envv('REMINDER_SMS_FALLBACK_TO', ''));
        if ($smsTo !== '') {
            $alreadySms = db_one(
                'SELECT id FROM subscription_reminders WHERE subscription_id = :subscription_id AND reminder_type = :reminder_type AND channel = :channel LIMIT 1',
                ['subscription_id' => $row['id'], 'reminder_type' => $reminderType, 'channel' => 'sms']
            );
            if (!$alreadySms) {
                $ok = send_sms_notification($smsTo, $message);
                db_exec_sql(
                    'INSERT INTO subscription_reminders
                     (id, subscription_id, student_id, reminder_type, channel, sent_to, message, sent_at, created_at)
                     VALUES
                     (:id, :subscription_id, :student_id, :reminder_type, :channel, :sent_to, :message, :sent_at, :created_at)',
                    [
                        'id' => uuid_v4(),
                        'subscription_id' => $row['id'],
                        'student_id' => $row['student_id'],
                        'reminder_type' => $reminderType,
                        'channel' => 'sms',
                        'sent_to' => $smsTo,
                        'message' => $ok ? $message : 'SMS delivery failed: ' . $message,
                        'sent_at' => now_sql(),
                        'created_at' => now_sql(),
                    ]
                );
                $sent++;
            }
        }
    }

    db_exec_sql(
        'UPDATE student_subscriptions
         SET status = "expired", updated_at = :updated_at
         WHERE status = "active" AND expiry_date < :now_ts',
        ['updated_at' => now_sql(), 'now_ts' => now_sql()]
    );
    $studentIds = db_all('SELECT DISTINCT student_id FROM student_subscriptions');
    foreach ($studentIds as $item) {
        if (!empty($item['student_id'])) {
            sync_student_subscription_state((string) $item['student_id']);
        }
    }

    json_response([
        'ok' => true,
        'sent' => $sent,
        'skipped' => $skipped,
        'ranAt' => gmdate('c'),
    ]);
}
