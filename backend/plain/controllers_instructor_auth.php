<?php

function ensure_instructor_auth_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS instructors (
            id CHAR(36) PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            mobile VARCHAR(30) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS otp_verifications (
            id CHAR(36) PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            otp VARCHAR(255) NOT NULL,
            previous_otp VARCHAR(255) NULL,
            previous_expiry_time DATETIME NULL,
            expiry_time DATETIME NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            last_sent_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_otp_email (email),
            INDEX idx_otp_expiry (expiry_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (!billing_table_has_column('otp_verifications', 'last_sent_at')) {
        db_exec_sql('ALTER TABLE otp_verifications ADD COLUMN last_sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER attempts');
    }
    if (!billing_table_has_column('otp_verifications', 'previous_otp')) {
        db_exec_sql('ALTER TABLE otp_verifications ADD COLUMN previous_otp VARCHAR(255) NULL AFTER otp');
    }
    if (!billing_table_has_column('otp_verifications', 'previous_expiry_time')) {
        db_exec_sql('ALTER TABLE otp_verifications ADD COLUMN previous_expiry_time DATETIME NULL AFTER previous_otp');
    }
    if (!billing_table_has_column('otp_verifications', 'created_at')) {
        db_exec_sql('ALTER TABLE otp_verifications ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER last_sent_at');
    }
}

function instructor_normalize_email(array $data): string
{
    return strtolower(trim((string) ($data['email'] ?? '')));
}

function instructor_validate_mobile(string $mobile): bool
{
    return preg_match('/^[0-9+\-\s()]{7,20}$/', $mobile) === 1;
}

function instructor_otp_hash(string $otp): string
{
    return password_hash($otp, PASSWORD_BCRYPT);
}

function instructor_utc_timestamp(string $value): int
{
    $timestamp = strtotime($value . ' UTC');
    return $timestamp === false ? 0 : $timestamp;
}

function instructor_is_dev_email_mode(): bool
{
    $mode = strtolower((string) envv('INSTRUCTOR_OTP_EMAIL_MODE', ''));
    if (in_array($mode, ['smtp', 'mail', 'live'], true)) {
        return false;
    }
    if (in_array($mode, ['dev', 'mock', 'log'], true)) {
        return true;
    }

    $enabled = strtolower((string) envv('EMAIL_ENABLED', 'true'));
    if (in_array($enabled, ['0', 'false', 'no', 'off'], true)) {
        return true;
    }

    return false;
}

function instructor_send_html_mail(string $to, string $subject, string $html, string $text): bool
{
    if (instructor_is_dev_email_mode()) {
        error_log('Instructor OTP email skipped in development mode for ' . $to);
        return true;
    }

    $timeout = max(3, (int) envv('EMAIL_TIMEOUT_SECONDS', '10'));
    $fromRaw = (string) envv('EMAIL_FROM', (string) envv('MAIL_FROM_ADDRESS', 'Abacus Trainer <no-reply@abacustrainer.com>'));
    $fromEmail = $fromRaw;
    $fromName = 'Abacus Trainer';
    if (preg_match('/^(.*?)<([^>]+)>$/', $fromRaw, $m) === 1) {
        $fromName = trim(trim($m[1]), '"') ?: $fromName;
        $fromEmail = trim($m[2]);
    }

    if (class_exists('\\Symfony\\Component\\Mailer\\Transport') && class_exists('\\Symfony\\Component\\Mime\\Email')) {
        try {
            $host = (string) envv('EMAIL_HOST', (string) envv('MAIL_HOST', ''));
            $port = (int) envv('EMAIL_PORT', (string) envv('MAIL_PORT', '587'));
            $user = (string) envv('EMAIL_USER', (string) envv('MAIL_USERNAME', ''));
            $pass = (string) envv('EMAIL_PASS', (string) envv('MAIL_PASSWORD', ''));
            if ($host !== '' && $user !== '') {
                $scheme = $port === 465 ? 'smtps' : 'smtp';
                $dsn = sprintf('%s://%s:%s@%s:%d?timeout=%d', $scheme, rawurlencode($user), rawurlencode($pass), $host, $port, $timeout);
                $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
                $mailer = new \Symfony\Component\Mailer\Mailer($transport);
                $email = (new \Symfony\Component\Mime\Email())
                    ->from(new \Symfony\Component\Mime\Address($fromEmail, $fromName))
                    ->to($to)
                    ->subject($subject)
                    ->text($text)
                    ->html($html);
                $mailer->send($email);
                return true;
            }
        } catch (Throwable $e) {
            error_log('Instructor OTP SMTP failed: ' . $e->getMessage());
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromRaw,
        'Reply-To: ' . $fromEmail,
    ];
    $previousTimeout = ini_get('default_socket_timeout');
    @ini_set('default_socket_timeout', (string) $timeout);
    $sent = @mail($to, $subject, $html, implode("\r\n", $headers));
    if ($previousTimeout !== false) {
        @ini_set('default_socket_timeout', (string) $previousTimeout);
    }
    return $sent;
}

function instructor_send_otp_email(string $name, string $email, string $otp): bool
{
    $siteUrl = rtrim((string) envv('SITE_URL', 'https://abacustrainer.com'), '/');
    $logoUrl = $siteUrl . '/abacus_logo.png';
    $subject = 'Instructor Registration Verification - Abacus Trainer';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    $html = '<div style="margin:0;padding:0;background:#f6f3fb;font-family:Arial,sans-serif;color:#1f2937">'
        . '<div style="max-width:560px;margin:0 auto;padding:28px 16px">'
        . '<div style="background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #eadff7">'
        . '<div style="padding:24px;text-align:center;border-bottom:4px solid #f97316">'
        . '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="Abacus Trainer" style="max-height:64px;max-width:220px" />'
        . '</div><div style="padding:28px">'
        . '<h2 style="margin:0 0 12px;color:#4b1e83">Hello ' . $safeName . ',</h2>'
        . '<p style="font-size:15px;line-height:1.6;margin:0 0 10px">Thank you for registering as an instructor with Abacus Trainer.</p>'
        . '<p style="font-size:15px;line-height:1.6;margin:0 0 22px">Please use the OTP below to verify your email.</p>'
        . '<div style="text-align:center;margin:24px 0"><div style="display:inline-block;background:#4b1e83;color:#ffffff;font-size:34px;font-weight:700;letter-spacing:8px;padding:16px 24px;border-radius:12px">' . $safeOtp . '</div></div>'
        . '<p style="font-size:14px;color:#6b7280;margin:0">This OTP expires in <strong>5 minutes</strong>.</p>'
        . '</div><div style="background:#faf7ff;padding:16px 24px;text-align:center;color:#6b7280;font-size:13px">Abacus Trainer</div>'
        . '</div></div></div>';

    $text = "Hello {$name},\n\nThank you for registering as an instructor with Abacus Trainer.\nPlease use the OTP below to verify your email.\n\nOTP: {$otp}\n\nThis OTP expires in 5 minutes.\n\nAbacus Trainer";
    return instructor_send_html_mail($email, $subject, $html, $text);
}

function instructor_issue_otp(string $name, string $email): ?string
{
    $existingOtp = db_one('SELECT * FROM otp_verifications WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($existingOtp && !empty($existingOtp['last_sent_at']) && (time() - instructor_utc_timestamp((string) $existingOtp['last_sent_at'])) < 60) {
        json_response(['message' => 'Please wait before requesting another OTP'], 429);
    }

    $otp = (string) random_int(100000, 999999);
    $now = now_sql();
    $expiry = gmdate('Y-m-d H:i:s', time() + 300);
    if ($existingOtp) {
        db_exec_sql(
            'UPDATE otp_verifications
             SET previous_otp = otp, previous_expiry_time = expiry_time, otp = :otp, expiry_time = :expiry_time,
                 attempts = 0, last_sent_at = :last_sent_at
             WHERE email = :email',
            ['otp' => instructor_otp_hash($otp), 'expiry_time' => $expiry, 'last_sent_at' => $now, 'email' => $email]
        );
    } else {
        db_exec_sql(
            'INSERT INTO otp_verifications (id, email, otp, expiry_time, attempts, last_sent_at, created_at)
             VALUES (:id, :email, :otp, :expiry_time, 0, :last_sent_at, :created_at)',
            ['id' => uuid_v4(), 'email' => $email, 'otp' => instructor_otp_hash($otp), 'expiry_time' => $expiry, 'last_sent_at' => $now, 'created_at' => $now]
        );
    }

    if (!instructor_send_otp_email($name, $email, $otp)) {
        json_response(['message' => 'Unable to send OTP email. Please check SMTP settings on the live backend.'], 500);
    }

    return instructor_is_dev_email_mode() ? $otp : null;
}

function controller_instructor_register_start(array $data): void
{
    ensure_instructor_auth_schema();
    $name = trim((string) ($data['fullName'] ?? $data['name'] ?? ''));
    $mobile = trim((string) ($data['mobile'] ?? ''));
    $email = instructor_normalize_email($data);

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !instructor_validate_mobile($mobile)) {
        json_response(['message' => 'Please enter a valid name, mobile number, and email address'], 422);
    }

    $instructor = db_one('SELECT * FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($instructor && (int) ($instructor['is_verified'] ?? 0) === 1 && !empty($instructor['password'])) {
        json_response(['message' => 'This email is already registered. Please login.'], 409);
    }

    $now = now_sql();
    if ($instructor) {
        db_exec_sql(
            'UPDATE instructors SET full_name = :full_name, mobile = :mobile WHERE email = :email',
            ['full_name' => $name, 'mobile' => $mobile, 'email' => $email]
        );
    } else {
        db_exec_sql(
            'INSERT INTO instructors (id, full_name, mobile, email, password, is_verified, created_at)
             VALUES (:id, :full_name, :mobile, :email, NULL, 0, :created_at)',
            ['id' => uuid_v4(), 'full_name' => $name, 'mobile' => $mobile, 'email' => $email, 'created_at' => $now]
        );
    }

    $devOtp = instructor_issue_otp($name, $email);
    $payload = ['message' => 'OTP sent to your email', 'email' => $email];
    if ($devOtp !== null) {
        $payload['devOtp'] = $devOtp;
        $payload['message'] = 'Development OTP generated. Use the code shown on the next screen.';
    }
    json_response($payload);
}

function controller_instructor_verify_otp(array $data): void
{
    ensure_instructor_auth_schema();
    $email = instructor_normalize_email($data);
    $otp = trim((string) ($data['otp'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9]{6}$/', $otp)) {
        json_response(['message' => 'Invalid OTP request'], 422);
    }

    $row = db_one('SELECT * FROM otp_verifications WHERE email = :email LIMIT 1', ['email' => $email]);
    if (!$row) {
        $instructor = db_one('SELECT id FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
        if ($instructor) {
            json_response(['message' => 'OTP not found. A new OTP is required.', 'resendRequired' => true], 404);
        }
        json_response(['message' => 'Instructor registration not found. Please register again.'], 404);
    }
    if ((int) ($row['attempts'] ?? 0) >= 3) {
        json_response(['message' => 'Maximum OTP attempts reached. Please resend OTP.'], 429);
    }
    if (instructor_utc_timestamp((string) $row['expiry_time']) < time()) {
        json_response(['message' => 'OTP expired. Please resend OTP.', 'expired' => true], 410);
    }
    $matchesCurrent = password_verify($otp, (string) $row['otp']);
    $matchesPrevious = !empty($row['previous_otp'])
        && !empty($row['previous_expiry_time'])
        && instructor_utc_timestamp((string) $row['previous_expiry_time']) >= time()
        && password_verify($otp, (string) $row['previous_otp']);

    if (!$matchesCurrent && !$matchesPrevious) {
        db_exec_sql('UPDATE otp_verifications SET attempts = attempts + 1 WHERE email = :email', ['email' => $email]);
        json_response(['message' => 'Incorrect OTP. Please try again.'], 401);
    }

    db_exec_sql('UPDATE instructors SET is_verified = 1 WHERE email = :email', ['email' => $email]);
    json_response(['message' => 'Email verified successfully', 'email' => $email]);
}

function controller_instructor_resend_otp(array $data): void
{
    ensure_instructor_auth_schema();
    $email = instructor_normalize_email($data);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'Invalid email address'], 422);
    }
    $instructor = db_one('SELECT * FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
    if (!$instructor) {
        json_response(['message' => 'Instructor registration not found'], 404);
    }
    $devOtp = instructor_issue_otp((string) $instructor['full_name'], $email);
    $payload = ['message' => 'A new OTP has been sent'];
    if ($devOtp !== null) {
        $payload['devOtp'] = $devOtp;
        $payload['message'] = 'Development OTP regenerated. Use the code shown on this screen.';
    }
    json_response($payload);
}

function controller_instructor_set_password(array $data): void
{
    ensure_instructor_auth_schema();
    $email = instructor_normalize_email($data);
    $password = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirmPassword'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $password !== $confirm) {
        json_response(['message' => 'Please enter matching passwords with at least 6 characters'], 422);
    }

    $instructor = db_one('SELECT * FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
    if (!$instructor || (int) ($instructor['is_verified'] ?? 0) !== 1) {
        json_response(['message' => 'Please verify your email before setting password'], 403);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $now = now_sql();
    db_exec_sql('UPDATE instructors SET password = :password WHERE email = :email', ['password' => $hash, 'email' => $email]);

    $pdo = db_conn();
    $pdo->beginTransaction();
    try {
        $user = db_one('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
        if ($user) {
            db_exec_sql(
                'UPDATE users SET name = :name, password = :password, role = :role, updated_at = :updated_at WHERE id = :id',
                ['name' => $instructor['full_name'], 'password' => $hash, 'role' => 'tutor', 'updated_at' => $now, 'id' => $user['id']]
            );
            $userId = (string) $user['id'];
        } else {
            $userId = uuid_v4();
            db_exec_sql(
                'INSERT INTO users (id, name, email, password, role, created_at, updated_at)
                 VALUES (:id, :name, :email, :password, :role, :created_at, :updated_at)',
                ['id' => $userId, 'name' => $instructor['full_name'], 'email' => $email, 'password' => $hash, 'role' => 'tutor', 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $tutor = db_one('SELECT id FROM tutors WHERE user_id = :user_id LIMIT 1', ['user_id' => $userId]);
        if (!$tutor) {
            db_exec_sql(
                'INSERT INTO tutors (id, user_id, created_at, updated_at) VALUES (:id, :user_id, :created_at, :updated_at)',
                ['id' => uuid_v4(), 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]
            );
        }
        db_exec_sql('DELETE FROM otp_verifications WHERE email = :email', ['email' => $email]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Instructor password setup failed: ' . $e->getMessage());
        json_response(['message' => 'Unable to complete registration'], 500);
    }

    json_response(['message' => 'Password set successfully. You can now login.']);
}
