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
            course_type VARCHAR(60) NULL,
            country_code VARCHAR(10) NULL,
            gender VARCHAR(30) NULL,
            date_of_birth DATE NULL,
            qualification VARCHAR(255) NULL,
            experience VARCHAR(120) NULL,
            career_started VARCHAR(100) NULL,
            students_trained VARCHAR(100) NULL,
            address TEXT NULL,
            profile_picture TEXT NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            role VARCHAR(30) NOT NULL DEFAULT \'instructor\',
            status VARCHAR(30) NOT NULL DEFAULT \'pending\',
            reset_token VARCHAR(64) NULL,
            reset_expiry DATETIME NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $instructorColumns = [
        'role' => "VARCHAR(30) NOT NULL DEFAULT 'instructor'",
        'status' => "VARCHAR(30) NOT NULL DEFAULT 'pending'",
        'course_type' => 'VARCHAR(60) NULL',
        'country_code' => 'VARCHAR(10) NULL',
        'gender' => 'VARCHAR(30) NULL',
        'date_of_birth' => 'DATE NULL',
        'qualification' => 'VARCHAR(255) NULL',
        'experience' => 'VARCHAR(120) NULL',
        'career_started' => 'VARCHAR(100) NULL',
        'students_trained' => 'VARCHAR(100) NULL',
        'address' => 'TEXT NULL',
        'profile_picture' => 'TEXT NULL',
        'reset_token' => 'VARCHAR(64) NULL',
        'reset_expiry' => 'DATETIME NULL',
    ];
    foreach ($instructorColumns as $column => $definition) {
        if (!billing_table_has_column('instructors', $column)) {
            db_exec_sql("ALTER TABLE instructors ADD COLUMN {$column} {$definition}");
        }
    }

    db_exec_sql("UPDATE instructors SET role = 'instructor' WHERE role IS NULL OR role = ''");
    db_exec_sql("UPDATE instructors SET status = CASE WHEN is_verified = 1 THEN 'approved' ELSE 'pending' END WHERE status IS NULL OR status = ''");
    db_exec_sql("UPDATE instructors SET status = 'approved' WHERE is_verified = 1 AND status = 'pending'");
}

function instructor_normalize_email(array $data): string
{
    return strtolower(trim((string) ($data['email'] ?? '')));
}

function instructor_validate_mobile(string $mobile): bool
{
    return preg_match('/^[0-9+\-\s()]{7,20}$/', $mobile) === 1;
}

function instructor_normalize_course_type(string $courseType): string
{
    $courseType = strtolower(trim($courseType));
    if (in_array($courseType, ['abacus', 'vedic_maths'], true)) {
        return $courseType;
    }
    if (in_array($courseType, ['vedic maths', 'vedic-maths', 'vedic'], true)) {
        return 'vedic_maths';
    }
    return '';
}

function instructor_handle_profile_picture(): string
{
    if (!isset($_FILES['profilePicture']) || !is_array($_FILES['profilePicture'])) {
        return '';
    }
    if (($_FILES['profilePicture']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($_FILES['profilePicture']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        json_response(['message' => 'Profile picture upload failed. Please try again.'], 422);
    }
    if ((int) ($_FILES['profilePicture']['size'] ?? 0) > 2 * 1024 * 1024) {
        json_response(['message' => 'Profile picture must be 2MB or smaller.'], 422);
    }

    $tmp = (string) ($_FILES['profilePicture']['tmp_name'] ?? '');
    $mime = $tmp !== '' && function_exists('mime_content_type') ? (string) mime_content_type($tmp) : '';
    if ($mime !== '' && !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        json_response(['message' => 'Profile picture must be a JPG, PNG, or WebP image.'], 422);
    }

    return handle_upload_file('profilePicture');
}

function instructor_validate_password(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must include uppercase, lowercase, and number characters.';
    }
    return null;
}

function instructor_utc_timestamp(string $value): int
{
    $timestamp = strtotime($value . ' UTC');
    return $timestamp === false ? 0 : $timestamp;
}

function instructor_is_email_disabled(): bool
{
    $enabled = strtolower((string) envv('EMAIL_ENABLED', 'true'));
    return in_array($enabled, ['0', 'false', 'no', 'off'], true);
}

function instructor_parse_mail_address(string $raw, string $defaultName): array
{
    $email = trim($raw);
    $name = $defaultName;
    if (preg_match('/^(.*?)<([^>]+)>$/', $raw, $m) === 1) {
        $name = trim(trim($m[1]), '"') ?: $defaultName;
        $email = trim($m[2]);
    }

    return [$email, $name];
}

function instructor_send_brevo_api_mail(string $to, string $subject, string $html, string $text, string $fromRaw): ?bool
{
    $apiKey = trim((string) envv('BREVO_API_KEY', (string) envv('SENDINBLUE_API_KEY', '')));
    if ($apiKey === '') {
        return null;
    }

    [$fromEmail, $fromName] = instructor_parse_mail_address($fromRaw, 'Simple Abacus');
    $timeout = max(3, (int) envv('EMAIL_TIMEOUT_SECONDS', '10'));
    $payload = [
        'sender' => ['email' => $fromEmail, 'name' => $fromName],
        'to' => [['email' => $to]],
        'subject' => $subject,
        'htmlContent' => $html,
        'textContent' => $text,
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        error_log('Brevo API email failed: unable to encode payload');
        return false;
    }

    if (!function_exists('curl_init')) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'api-key: ' . $apiKey,
                ]),
                'content' => $jsonPayload,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $context);
        $status = 0;
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $m) === 1) {
                $status = (int) $m[1];
                break;
            }
        }
        if ($response === false || $status < 200 || $status >= 300) {
            error_log('Brevo API email failed without cURL: status=' . $status . ' response=' . (string) $response);
            return false;
        }
        return true;
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'api-key: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        error_log('Brevo API email failed: status=' . $status . ' error=' . $error . ' response=' . (string) $response);
        return false;
    }

    return true;
}

function instructor_smtp_expect($socket, array $expectedCodes, string $step): string
{
    $response = '';
    while (($line = fgets($socket, 2048)) !== false) {
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException($step . ' failed with SMTP response: ' . trim($response));
    }

    return $response;
}

function instructor_smtp_command($socket, string $command, array $expectedCodes, string $step): string
{
    if (fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException($step . ' failed while writing to SMTP server');
    }

    return instructor_smtp_expect($socket, $expectedCodes, $step);
}

function instructor_smtp_data_line(string $value): string
{
    return preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $value)) ?? '';
}

function instructor_send_smtp_mail(
    string $to,
    string $subject,
    string $html,
    string $text,
    string $fromEmail,
    string $fromName,
    string $host,
    int $port,
    string $user,
    string $pass,
    int $timeout
): bool {
    if (!function_exists('stream_socket_client')) {
        error_log('Instructor email SMTP failed: stream_socket_client is not available');
        return false;
    }

    $remote = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log('Instructor email SMTP failed: connection error ' . $errno . ' ' . $errstr);
        return false;
    }

    stream_set_timeout($socket, $timeout);
    $boundary = 'abacus_mail_' . bin2hex(random_bytes(12));
    $domain = preg_match('/@([^@]+)$/', $fromEmail, $m) === 1 ? $m[1] : 'abacustrainer.com';
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
        'From: ' . mail_header_address($fromEmail, $fromName),
        'To: ' . mail_header_address($to),
        'Subject: ' . $encodedSubject,
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    $message = implode("\r\n", $headers)
        . "\r\n\r\n--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
        . instructor_smtp_data_line($text)
        . "\r\n--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
        . instructor_smtp_data_line($html)
        . "\r\n--{$boundary}--\r\n";

    try {
        instructor_smtp_expect($socket, [220], 'Greeting');
        $ehlo = instructor_smtp_command($socket, 'EHLO ' . $domain, [250], 'EHLO');
        if ($port !== 465 && stripos($ehlo, 'STARTTLS') !== false) {
            instructor_smtp_command($socket, 'STARTTLS', [220], 'STARTTLS');
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS negotiation failed');
            }
            instructor_smtp_command($socket, 'EHLO ' . $domain, [250], 'EHLO after STARTTLS');
        }

        if ($user !== '') {
            instructor_smtp_command($socket, 'AUTH LOGIN', [334], 'AUTH LOGIN');
            instructor_smtp_command($socket, base64_encode($user), [334], 'SMTP username');
            instructor_smtp_command($socket, base64_encode($pass), [235], 'SMTP password');
        }

        instructor_smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250], 'MAIL FROM');
        instructor_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251], 'RCPT TO');
        instructor_smtp_command($socket, 'DATA', [354], 'DATA');
        instructor_smtp_command($socket, $message . "\r\n.", [250], 'Message body');
        instructor_smtp_command($socket, 'QUIT', [221, 250], 'QUIT');
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        fclose($socket);
        error_log('Instructor email SMTP failed: ' . $e->getMessage());
        return false;
    }
}

function instructor_send_html_mail(string $to, string $subject, string $html, string $text): bool
{
    if (instructor_is_email_disabled()) {
        error_log('Instructor email skipped because EMAIL_ENABLED is disabled for ' . $to);
        return false;
    }

    $timeout = max(3, (int) envv('EMAIL_TIMEOUT_SECONDS', '10'));
    $fromRaw = (string) envv('EMAIL_FROM', (string) envv('MAIL_FROM_ADDRESS', 'Simple Abacus <no-reply@simpleabacus.com>'));
    $apiSent = instructor_send_brevo_api_mail($to, $subject, $html, $text, $fromRaw);
    if ($apiSent !== null) {
        return $apiSent;
    }
    [$fromEmail, $fromName] = instructor_parse_mail_address($fromRaw, 'Simple Abacus');

    $host = (string) envv('EMAIL_HOST', (string) envv('MAIL_HOST', ''));
    $port = (int) envv('EMAIL_PORT', (string) envv('MAIL_PORT', '587'));
    $user = (string) envv('EMAIL_USER', (string) envv('MAIL_USERNAME', ''));
    $pass = (string) envv('EMAIL_PASS', (string) envv('MAIL_PASSWORD', ''));
    $hasSmtpConfig = $host !== '' && $user !== '';

    if ($hasSmtpConfig && instructor_send_smtp_mail($to, $subject, $html, $text, $fromEmail, $fromName, $host, $port, $user, $pass, $timeout)) {
        return true;
    }

    $allowSymfonyMailer = strtolower((string) envv('EMAIL_ALLOW_SYMFONY_MAILER', 'false')) === 'true';
    if ($allowSymfonyMailer && class_exists('\\Symfony\\Component\\Mailer\\Transport') && class_exists('\\Symfony\\Component\\Mime\\Email')) {
        try {
            if ($hasSmtpConfig) {
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
            error_log('Instructor email SMTP failed: ' . $e->getMessage());
        }
    }

    if (strtolower((string) envv('EMAIL_ALLOW_PHP_MAIL_FALLBACK', 'false')) !== 'true') {
        error_log('Instructor email failed: no working Brevo API/SMTP transport configured');
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromRaw,
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . phpversion(),
    ];
    $previousTimeout = ini_get('default_socket_timeout');
    @ini_set('default_socket_timeout', (string) $timeout);
    $envelope = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? '-f' . $fromEmail : '';
    $sent = @mail($to, $subject, $html, implode("\r\n", $headers), $envelope);
    if ($previousTimeout !== false) {
        @ini_set('default_socket_timeout', (string) $previousTimeout);
    }
    return $sent;
}

function instructor_frontend_url(): string
{
    $configured = trim((string) envv('FRONTEND_URL', (string) envv('SITE_URL', '')));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $origin = request_header('Origin');
    return rtrim($origin ?: get_base_url(), '/');
}

function instructor_send_password_reset_email(string $name, string $email, string $resetUrl): bool
{
    $subject = 'Reset your Simple Abacus instructor password';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $html = '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6">'
        . '<h2 style="color:#4b1e83">Hello ' . $safeName . ',</h2>'
        . '<p>We received a request to reset your instructor password.</p>'
        . '<p><a href="' . $safeUrl . '" style="display:inline-block;background:#4b1e83;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px">Reset Password</a></p>'
        . '<p>This link expires in 1 hour. If you did not request this, you can ignore this email.</p>'
        . '<p style="font-size:13px;color:#6b7280">If the button does not work, copy this link: ' . $safeUrl . '</p>'
        . '</div>';
    $text = "Hello {$name},\n\nReset your instructor password using this link:\n{$resetUrl}\n\nThis link expires in 1 hour.";

    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $fromRaw = (string) envv('EMAIL_FROM', (string) envv('MAIL_FROM_ADDRESS', 'Simple Abacus <no-reply@simpleabacus.com>'));
            [$fromEmail, $fromName] = instructor_parse_mail_address($fromRaw, 'Simple Abacus');
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $host = (string) envv('EMAIL_HOST', (string) envv('MAIL_HOST', ''));
            $user = (string) envv('EMAIL_USER', (string) envv('MAIL_USERNAME', ''));
            if ($host !== '' && $user !== '') {
                $mailer->isSMTP();
                $mailer->Host = $host;
                $mailer->Port = (int) envv('EMAIL_PORT', (string) envv('MAIL_PORT', '587'));
                $mailer->SMTPAuth = true;
                $mailer->Username = $user;
                $mailer->Password = (string) envv('EMAIL_PASS', (string) envv('MAIL_PASSWORD', ''));
                $mailer->SMTPSecure = $mailer->Port === 465
                    ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mailer->setFrom($fromEmail, $fromName);
            $mailer->addAddress($email, $name);
            $mailer->Subject = $subject;
            $mailer->isHTML(true);
            $mailer->Body = $html;
            $mailer->AltBody = $text;
            $mailer->send();
            return true;
        } catch (Throwable $e) {
            error_log('Instructor reset PHPMailer failed: ' . $e->getMessage());
        }
    }

    return instructor_send_html_mail($email, $subject, $html, $text);
}

function instructor_ensure_approved_user(array $instructor): string
{
    if (function_exists('ensure_training_schema')) {
        ensure_training_schema();
    }

    $email = strtolower((string) $instructor['email']);
    $user = db_one('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    $now = now_sql();
    if ($user) {
        db_exec_sql(
            'UPDATE users SET name = :name, password = :password, role = :role, training_status = :training_status, updated_at = :updated_at WHERE id = :id',
            [
                'name' => $instructor['full_name'],
                'password' => $instructor['password'],
                'role' => 'tutor',
                'training_status' => 'approved',
                'updated_at' => $now,
                'id' => $user['id'],
            ]
        );
        $userId = (string) $user['id'];
    } else {
        $userId = uuid_v4();
        db_exec_sql(
            'INSERT INTO users (id, name, email, password, role, training_status, created_at, updated_at)
             VALUES (:id, :name, :email, :password, :role, :training_status, :created_at, :updated_at)',
            [
                'id' => $userId,
                'name' => $instructor['full_name'],
                'email' => $email,
                'password' => $instructor['password'],
                'role' => 'tutor',
                'training_status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    $tutor = db_one('SELECT id FROM tutors WHERE user_id = :user_id LIMIT 1', ['user_id' => $userId]);
    if (!$tutor) {
        db_exec_sql(
            'INSERT INTO tutors (id, user_id, created_at, updated_at) VALUES (:id, :user_id, :created_at, :updated_at)',
            ['id' => uuid_v4(), 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]
        );
    }

    return $userId;
}

function controller_instructor_register_start(array $data): void
{
    ensure_instructor_auth_schema();
    $name = trim((string) ($data['fullName'] ?? $data['name'] ?? ''));
    $countryCode = trim((string) ($data['countryCode'] ?? ''));
    $mobileInput = trim((string) ($data['mobile'] ?? ''));
    $mobile = trim($countryCode . ' ' . $mobileInput);
    $email = instructor_normalize_email($data);
    $courseType = instructor_normalize_course_type((string) ($data['courseType'] ?? ''));
    $gender = strtolower(trim((string) ($data['gender'] ?? '')));
    $dateOfBirth = trim((string) ($data['dateOfBirth'] ?? ''));
    $qualification = trim((string) ($data['qualification'] ?? ''));
    $experience = trim((string) ($data['experience'] ?? ''));
    $careerStarted = trim((string) ($data['careerStarted'] ?? ''));
    $studentsTrained = trim((string) ($data['studentsTrained'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirmPassword'] ?? '');

    if (
        $courseType === ''
        || $name === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !instructor_validate_mobile($mobile)
        || !in_array($gender, ['male', 'female', 'other'], true)
        || $dateOfBirth === ''
        || strtotime($dateOfBirth) === false
        || $qualification === ''
        || $experience === ''
        || $careerStarted === ''
        || $studentsTrained === ''
        || $address === ''
    ) {
        json_response(['message' => 'Please fill all tutor registration fields with valid details.'], 422);
    }
    if ($password !== $confirm) {
        json_response(['message' => 'Passwords do not match.'], 422);
    }
    $passwordError = instructor_validate_password($password);
    if ($passwordError !== null) {
        json_response(['message' => $passwordError], 422);
    }

    $instructor = db_one('SELECT * FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($instructor) {
        $status = (string) ($instructor['status'] ?? 'pending');
        if ((int) ($instructor['is_verified'] ?? 0) === 1 || $status === 'approved') {
            json_response(['message' => 'This tutor account is already approved. Please login with your email and password.'], 409);
        }

        $now = now_sql();
        $uploadedProfilePicture = instructor_handle_profile_picture();
        $profilePicture = $uploadedProfilePicture !== '' ? $uploadedProfilePicture : (string) ($instructor['profile_picture'] ?? '');
        db_exec_sql(
            'UPDATE instructors SET
                full_name = :full_name,
                mobile = :mobile,
                password = :password,
                course_type = :course_type,
                country_code = :country_code,
                gender = :gender,
                date_of_birth = :date_of_birth,
                qualification = :qualification,
                experience = :experience,
                career_started = :career_started,
                students_trained = :students_trained,
                address = :address,
                profile_picture = :profile_picture,
                is_verified = 0,
                role = :role,
                status = :status,
                created_at = :created_at
             WHERE id = :id',
            [
                'full_name' => $name,
                'mobile' => $mobile,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'course_type' => $courseType,
                'country_code' => $countryCode,
                'gender' => $gender,
                'date_of_birth' => date('Y-m-d', strtotime($dateOfBirth)),
                'qualification' => $qualification,
                'experience' => $experience,
                'career_started' => $careerStarted,
                'students_trained' => $studentsTrained,
                'address' => $address,
                'profile_picture' => $profilePicture,
                'role' => 'instructor',
                'status' => 'pending',
                'created_at' => $now,
                'id' => $instructor['id'],
            ]
        );

        json_response(['message' => 'Registration submitted successfully. Wait for admin approval.', 'email' => $email], 200);
    }

    $now = now_sql();
    $profilePicture = instructor_handle_profile_picture();
    db_exec_sql(
        'INSERT INTO instructors (
            id, full_name, mobile, email, password, course_type, country_code, gender, date_of_birth,
            qualification, career_started, students_trained, address, profile_picture,
            experience,
            is_verified, role, status, created_at
         )
         VALUES (
            :id, :full_name, :mobile, :email, :password, :course_type, :country_code, :gender, :date_of_birth,
            :qualification, :career_started, :students_trained, :address, :profile_picture,
            :experience,
            0, :role, :status, :created_at
         )',
        [
            'id' => uuid_v4(),
            'full_name' => $name,
            'mobile' => $mobile,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'course_type' => $courseType,
            'country_code' => $countryCode,
            'gender' => $gender,
            'date_of_birth' => date('Y-m-d', strtotime($dateOfBirth)),
            'qualification' => $qualification,
            'experience' => $experience,
            'career_started' => $careerStarted,
            'students_trained' => $studentsTrained,
            'address' => $address,
            'profile_picture' => $profilePicture,
            'role' => 'instructor',
            'status' => 'pending',
            'created_at' => $now,
        ]
    );

    json_response(['message' => 'Registration submitted successfully. Wait for admin approval.', 'email' => $email], 201);
}

function controller_instructor_forgot_password(array $data): void
{
    ensure_instructor_auth_schema();
    $email = instructor_normalize_email($data);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'Please enter a valid email address'], 422);
    }

    $message = 'If an approved instructor account exists for this email, a password reset link has been sent.';
    $instructor = db_one('SELECT * FROM instructors WHERE email = :email LIMIT 1', ['email' => $email]);
    if (!$instructor || ($instructor['status'] ?? '') !== 'approved') {
        json_response(['message' => $message]);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiry = gmdate('Y-m-d H:i:s', time() + 3600);
    db_exec_sql(
        'UPDATE instructors SET reset_token = :reset_token, reset_expiry = :reset_expiry WHERE email = :email',
        ['reset_token' => $tokenHash, 'reset_expiry' => $expiry, 'email' => $email]
    );

    $resetUrl = instructor_frontend_url() . '/instructor-reset-password?email=' . rawurlencode($email) . '&token=' . rawurlencode($token);
    if (!instructor_send_password_reset_email((string) $instructor['full_name'], $email, $resetUrl)) {
        json_response(['message' => 'Unable to send reset email. Please try again later.'], 500);
    }

    json_response(['message' => $message]);
}

function controller_instructor_reset_password(array $data): void
{
    ensure_instructor_auth_schema();
    $email = instructor_normalize_email($data);
    $token = (string) ($data['token'] ?? '');
    $password = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirmPassword'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $token === '' || $password !== $confirm) {
        json_response(['message' => 'Invalid reset request.'], 422);
    }
    $passwordError = instructor_validate_password($password);
    if ($passwordError !== null) {
        json_response(['message' => $passwordError], 422);
    }

    $tokenHash = hash('sha256', $token);
    $instructor = db_one(
        'SELECT * FROM instructors WHERE email = :email AND reset_token = :reset_token LIMIT 1',
        ['email' => $email, 'reset_token' => $tokenHash]
    );
    if (!$instructor) {
        json_response(['message' => 'This reset link is invalid or expired.'], 400);
    }
    if (empty($instructor['reset_expiry']) || instructor_utc_timestamp((string) $instructor['reset_expiry']) < time()) {
        json_response(['message' => 'This reset link has expired. Please request a new one.'], 410);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    db_exec_sql(
        'UPDATE instructors SET password = :password, reset_token = NULL, reset_expiry = NULL WHERE email = :email',
        ['password' => $hash, 'email' => $email]
    );
    if (($instructor['status'] ?? '') === 'approved') {
        $instructor['password'] = $hash;
        instructor_ensure_approved_user($instructor);
    }

    json_response(['message' => 'Password reset successfully. You can now login.']);
}
