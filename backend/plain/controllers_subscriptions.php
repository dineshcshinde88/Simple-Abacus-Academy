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
         WHERE ss.student_id = :student_id AND ss.status = "active" AND ss.payment_status = "paid" AND ss.expiry_date >= :now_ts
         ORDER BY ss.expiry_date DESC
         LIMIT 1',
        ['student_id' => $studentId, 'now_ts' => $now]
    );

    if ($active) {
        db_exec_sql(
            'UPDATE students
             SET level_id = :level_id, subscription_plan = :plan_name, subscription_start = :start_date, subscription_end = :end_date,
                 subscription_status = :status, updated_at = :updated_at
             WHERE id = :student_id',
            [
                'level_id' => $active['level_id'] ?: null,
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
        'paymentStatus' => ($row['payment_status'] ?? '') === 'paid' ? 'paid' : 'unpaid',
        'razorpayOrderId' => $row['razorpay_order_id'] ?? null,
        'razorpayPaymentId' => $row['razorpay_payment_id'] ?? null,
        'createdAt' => $row['created_at'] ?? null,
        'updatedAt' => $row['updated_at'] ?? null,
    ];
}

function sync_paid_subscription_enrollment(string $subscriptionId): void
{
    ensure_billing_schema();

    $row = db_one(
        'SELECT ss.*, l.course_id
         FROM student_subscriptions ss
         LEFT JOIN levels l ON l.id = ss.level_id
         WHERE ss.id = :id AND ss.payment_status = "paid" AND ss.status = "active"
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
}

function repair_student_course_enrollments(string $studentId): void
{
    ensure_billing_schema();

    $rows = db_all(
        'SELECT id FROM student_subscriptions
         WHERE student_id = :student_id AND status = "active" AND payment_status = "paid" AND expiry_date >= :now_ts',
        ['student_id' => $studentId, 'now_ts' => now_sql()]
    );

    foreach ($rows as $row) {
        try {
            sync_paid_subscription_enrollment((string) $row['id']);
        } catch (Throwable $e) {
            error_log('Course assignment failed for subscription ' . ($row['id'] ?? '') . ': ' . $e->getMessage());
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

function create_missing_subscriptions_for_paid_attempt(array $attempt): void
{
    ensure_billing_schema();

    if (($attempt['status'] ?? '') !== 'paid') {
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
        return;
    }

    $planIds = paid_attempt_plan_ids($attempt);
    if (!$planIds) {
        error_log('Paid payment attempt has no plan ids: ' . $attemptId);
        return;
    }

    $placeholders = implode(',', array_fill(0, count($planIds), '?'));
    $plans = db_all("SELECT * FROM subscription_plans WHERE id IN ({$placeholders})", $planIds);
    if (count($plans) !== count($planIds)) {
        error_log('Paid payment attempt has missing subscription plans: ' . $attemptId);
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
            continue;
        }

        $existing = db_one(
            'SELECT * FROM student_subscriptions
             WHERE student_id = :student_id AND level_id <=> :level_id AND status = "active" AND payment_status = "paid"
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

        sync_paid_subscription_enrollment($subscriptionId);

        db_exec_sql(
            'UPDATE students
             SET level_id = :level_id, subscription_plan = :plan_name, subscription_start = :start_date, subscription_end = :end_date, subscription_status = :status, updated_at = :updated_at
             WHERE id = :student_id',
            [
                'level_id' => $plan['level_id'] ?: null,
                'plan_name' => $plan['name'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'updated_at' => $now,
                'student_id' => $studentId,
            ]
        );
    }
}

function repair_paid_payment_attempt_subscriptions(string $studentId): void
{
    ensure_billing_schema();

    $attempts = db_all(
        'SELECT pa.*
         FROM payment_attempts pa
         WHERE pa.student_id = :student_id
           AND pa.status = "paid"
           AND NOT EXISTS (
             SELECT 1 FROM student_subscriptions ss WHERE ss.payment_attempt_id = pa.id
           )
         ORDER BY COALESCE(pa.paid_at, pa.created_at) ASC, pa.created_at ASC',
        ['student_id' => $studentId]
    );

    foreach ($attempts as $attempt) {
        try {
            create_missing_subscriptions_for_paid_attempt($attempt);
        } catch (Throwable $e) {
            error_log('Paid payment repair failed for attempt ' . ($attempt['id'] ?? '') . ': ' . $e->getMessage());
        }
    }
}

function get_student_subscription_overview(string $studentId): array
{
    repair_paid_payment_attempt_subscriptions($studentId);
    $current = sync_student_subscription_state($studentId);
    repair_student_course_enrollments($studentId);

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
        'current' => $current ? map_subscription_row($current) : null,
        'history' => array_map(static fn(array $row): array => map_subscription_row($row), $historyRows),
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

function controller_student_subscription_plans(array $ctx): void
{
    ensure_billing_schema();
    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $rows = db_all(
        'SELECT p.*, l.level_name, l.course_id, c.name AS course_name, c.slug AS course_slug
         FROM subscription_plans p
         LEFT JOIN levels l ON l.id = p.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         WHERE p.is_active = 1
         ORDER BY COALESCE(c.name, p.name), COALESCE(l.level_name, p.name), p.price ASC'
    );

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

    $rows = db_all(
        'SELECT p.*, l.level_name, l.course_id, c.name AS course_name, c.slug AS course_slug
         FROM subscription_plans p
         LEFT JOIN levels l ON l.id = p.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         WHERE p.is_active = 1
         ORDER BY COALESCE(c.name, p.name), COALESCE(l.level_name, p.name), p.price ASC'
    );

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
    $overview = get_student_subscription_overview($student['id']);

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

    $orderResp = razorpay_request('POST', '/orders', $orderPayload, $gateway['key_id'], $gateway['secret']);
    if (!($orderResp['ok'] ?? false)) {
        json_response(['message' => (string) ($orderResp['message'] ?? 'Failed to create payment order')], 502);
    }

    $rzOrder = (array) ($orderResp['data'] ?? []);
    $attemptId = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO payment_attempts
         (id, student_id, plan_id, provider, amount, currency, status, provider_order_id, metadata_json, created_at, updated_at)
         VALUES
         (:id, :student_id, :plan_id, :provider, :amount, :currency, :status, :provider_order_id, :metadata_json, :created_at, :updated_at)',
        [
            'id' => $attemptId,
            'student_id' => $student['id'],
            'plan_id' => $plans[0]['id'],
            'provider' => 'razorpay',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'created',
            'provider_order_id' => $rzOrder['id'] ?? null,
            'metadata_json' => json_encode(['order' => $rzOrder, 'plan_ids' => $planIds], JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ]
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

function finalize_paid_payment_attempt(array $attempt, string $paymentId, string $signature = ''): array
{
    ensure_billing_schema();

    $attemptId = (string) $attempt['id'];
    $studentId = (string) $attempt['student_id'];
    $orderId = (string) ($attempt['provider_order_id'] ?? '');

    if (($attempt['status'] ?? '') === 'paid') {
        repair_student_course_enrollments($studentId);
        return get_student_subscription_overview($studentId);
    }

    $metadata = json_decode((string) ($attempt['metadata_json'] ?? ''), true);
    $planIds = [];
    if (is_array($metadata) && isset($metadata['plan_ids']) && is_array($metadata['plan_ids'])) {
        $planIds = array_values(array_filter(array_map(static fn($id): string => trim((string) $id), $metadata['plan_ids'])));
    }
    if (!$planIds) {
        $planIds = [(string) $attempt['plan_id']];
    }

    $placeholders = implode(',', array_fill(0, count($planIds), '?'));
    $plans = db_all("SELECT * FROM subscription_plans WHERE id IN ({$placeholders})", $planIds);
    if (count($plans) !== count($planIds)) {
        json_response(['message' => 'Plan not found for this payment'], 404);
    }

    foreach ($plans as $plan) {
        if ((int) ($plan['duration_days'] ?? 0) <= 0) {
            json_response(['message' => 'Invalid plan duration'], 422);
        }
    }

    $now = now_sql();
    $nowTs = time();
    $pdo = db_conn();
    $pdo->beginTransaction();

    try {
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, provider_payment_id = :payment_id, provider_signature = :signature, paid_at = :paid_at, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'paid',
                'payment_id' => $paymentId,
                'signature' => $signature !== '' ? $signature : ($attempt['provider_signature'] ?? null),
                'paid_at' => $now,
                'updated_at' => $now,
                'id' => $attemptId,
            ]
        );

        foreach ($plans as $plan) {
            $days = (int) ($plan['duration_days'] ?? 0);
            $existing = db_one(
                'SELECT * FROM student_subscriptions
                 WHERE student_id = :student_id AND level_id <=> :level_id AND status = "active" AND payment_status = "paid"
                 ORDER BY expiry_date DESC
                 LIMIT 1',
                [
                    'student_id' => $studentId,
                    'level_id' => $plan['level_id'] ?? null,
                ]
            );

            $baseExpiryTs = $nowTs;
            if ($existing && !empty($existing['expiry_date'])) {
                $existingExpiry = strtotime((string) $existing['expiry_date']);
                if ($existingExpiry !== false && $existingExpiry > $baseExpiryTs) {
                    $baseExpiryTs = $existingExpiry;
                }
            }

            $startDate = gmdate('Y-m-d H:i:s', $nowTs);
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
                    'razorpay_order_id' => $orderId,
                    'razorpay_payment_id' => $paymentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            sync_paid_subscription_enrollment($subscriptionId);

            db_exec_sql(
                'UPDATE students
                 SET level_id = :level_id, subscription_plan = :plan_name, subscription_start = :start_date, subscription_end = :end_date, subscription_status = :status, updated_at = :updated_at
                 WHERE id = :student_id',
                [
                    'level_id' => $plan['level_id'] ?: null,
                    'plan_name' => count($plans) === 1 ? $plan['name'] : count($plans) . ' worksheet levels',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'active',
                    'updated_at' => $now,
                    'student_id' => $studentId,
                ]
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Payment activation/course assignment failed for attempt ' . $attemptId . ': ' . $e->getMessage());
        json_response(['message' => 'Unable to activate subscription'], 500);
    }

    return get_student_subscription_overview($studentId);
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

    if (($attempt['provider_order_id'] ?? '') !== $orderId) {
        json_response(['message' => 'Payment order mismatch'], 422);
    }

    $gateway = get_payment_gateway_config('razorpay');
    if ($gateway['secret'] === '') {
        json_response(['message' => 'Razorpay secret is not configured'], 500);
    }

    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $gateway['secret']);
    if (!hash_equals($expected, $signature)) {
        db_exec_sql(
            'UPDATE payment_attempts
             SET status = :status, provider_payment_id = :payment_id, provider_signature = :signature, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'failed',
                'payment_id' => $paymentId,
                'signature' => $signature,
                'updated_at' => now_sql(),
                'id' => $attemptId,
            ]
        );
        json_response(['message' => 'Payment signature verification failed'], 422);
    }

    $overview = finalize_paid_payment_attempt($attempt, $paymentId, $signature);
    json_response(['message' => 'Subscription activated', 'subscription' => $overview]);
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
    $webhookSecret = (string) envv('RAZORPAY_WEBHOOK_SECRET', envv('PAYMENT_WEBHOOK_SECRET', ''));
    if ($webhookSecret !== '') {
        $receivedSignature = (string) (request_header('X-Razorpay-Signature') ?? request_header('payment-signature') ?? '');
        $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);
        if ($receivedSignature === '' || !hash_equals($expectedSignature, $receivedSignature)) {
            error_log('Razorpay webhook signature verification failed for event ' . $event);
            json_response(['message' => 'Invalid webhook signature'], 401);
        }
    }

    $paidEvents = ['payment.captured', 'payment.authorized', 'order.paid'];
    if (!in_array($event, $paidEvents, true)) {
        return ['processed' => false, 'message' => 'Webhook ignored'];
    }

    $payment = is_array($data['payload']['payment']['entity'] ?? null) ? $data['payload']['payment']['entity'] : [];
    $order = is_array($data['payload']['order']['entity'] ?? null) ? $data['payload']['order']['entity'] : [];
    $orderId = trim((string) ($payment['order_id'] ?? $order['id'] ?? $data['razorpayOrderId'] ?? ''));
    $paymentId = trim((string) ($payment['id'] ?? $data['razorpayPaymentId'] ?? ''));

    if ($orderId === '' || $paymentId === '') {
        error_log('Razorpay webhook missing order/payment id: ' . json_encode(['event' => $event], JSON_UNESCAPED_SLASHES));
        return ['processed' => false, 'message' => 'Webhook missing order/payment id'];
    }

    $attempt = db_one(
        'SELECT * FROM payment_attempts WHERE provider = "razorpay" AND provider_order_id = :order_id LIMIT 1',
        ['order_id' => $orderId]
    );
    if (!$attempt) {
        error_log('Razorpay webhook payment attempt not found for order ' . $orderId);
        return ['processed' => false, 'message' => 'Payment attempt not found'];
    }

    $overview = finalize_paid_payment_attempt($attempt, $paymentId, '');
    return [
        'processed' => true,
        'message' => 'Payment processed',
        'studentId' => $attempt['student_id'] ?? null,
        'subscription' => $overview,
    ];
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
            pa.status AS payment_attempt_status
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
        return $payload;
    }, $rows);

    json_response(['subscriptions' => $subscriptions]);
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
    if (!in_array($paymentStatus, ['paid', 'unpaid'], true)) {
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
           AND ss.payment_status = "paid"
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
