<?php

function mail_header_address(string $email, string $name = ''): string
{
    $safeEmail = str_replace(["\r", "\n"], '', trim($email));
    $safeName = str_replace(["\r", "\n", '"'], '', trim($name));

    return $safeName !== '' ? '"' . $safeName . '" <' . $safeEmail . '>' : $safeEmail;
}

function mail_sender_identity(): array
{
    $fromName = (string) envv('MAIL_FROM_NAME', (string) envv('EMAIL_FROM_NAME', 'Simple Abacus'));
    $fromEmail = (string) envv('MAIL_FROM_ADDRESS', (string) envv('EMAIL_FROM_ADDRESS', ''));
    $combined = (string) envv('EMAIL_FROM', '');

    if ($fromEmail === '' && preg_match('/^(.*?)<([^>]+)>$/', $combined, $m) === 1) {
        $fromName = trim(trim($m[1]), '"') ?: $fromName;
        $fromEmail = trim($m[2]);
    } elseif ($fromEmail === '' && filter_var($combined, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = $combined;
    }

    if ($fromEmail === '') {
        $fromEmail = (string) envv('EMAIL_USER', (string) envv('MAIL_USERNAME', 'no-reply@simpleabacus.com'));
    }

    return [$fromEmail, $fromName];
}

function send_plain_mail(string $to, string $subject, string $body, string $replyTo = '', string $replyName = ''): void
{
    [$fromEmail, $fromName] = mail_sender_identity();
    $from = mail_header_address($fromEmail, $fromName);
    $replyTo = filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? (string) $replyTo : $fromEmail;
    $reply = mail_header_address($replyTo, $replyName);

    if (class_exists('\\Symfony\\Component\\Mailer\\Transport') && class_exists('\\Symfony\\Component\\Mime\\Email')) {
        try {
            $host = (string) envv('EMAIL_HOST', (string) envv('MAIL_HOST', ''));
            $port = (int) envv('EMAIL_PORT', (string) envv('MAIL_PORT', '587'));
            $user = (string) envv('EMAIL_USER', (string) envv('MAIL_USERNAME', ''));
            $pass = (string) envv('EMAIL_PASS', (string) envv('MAIL_PASSWORD', ''));
            if ($host !== '' && $user !== '') {
                $scheme = $port === 465 ? 'smtps' : 'smtp';
                $dsn = sprintf('%s://%s:%s@%s:%d?timeout=5', $scheme, rawurlencode($user), rawurlencode($pass), $host, $port);
                $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
                $mailer = new \Symfony\Component\Mailer\Mailer($transport);
                $email = (new \Symfony\Component\Mime\Email())
                    ->from(new \Symfony\Component\Mime\Address($fromEmail, $fromName))
                    ->to($to)
                    ->replyTo(new \Symfony\Component\Mime\Address($replyTo, $replyName ?: $replyTo))
                    ->subject($subject)
                    ->text($body);
                $mailer->send($email);
                return;
            }
        } catch (Throwable $e) {
            error_log('Notification SMTP failed: ' . $e->getMessage());
        }
    }

    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $reply,
        'X-Mailer: PHP/' . phpversion(),
    ];
    $envelope = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? '-f' . $fromEmail : '';
    @mail($to, $subject, $body, implode("\r\n", $headers), $envelope);
}

function finish_json_response(array $payload, int $status = 200): bool
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return true;
    }

    if (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
        return true;
    }

    if (ob_get_level() > 0) {
        @ob_flush();
    }
    @flush();
    return false;
}

function ensure_demo_booking_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS demo_bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            preferred_date DATE NOT NULL,
            message TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function ensure_enquiry_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS website_enquiries (
            id CHAR(36) PRIMARY KEY,
            enquiry_type VARCHAR(40) NOT NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NULL,
            phone VARCHAR(20) NULL,
            subject VARCHAR(180) NULL,
            message TEXT NULL,
            details_json LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_website_enquiries_type_status (enquiry_type, status),
            INDEX idx_website_enquiries_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function save_website_enquiry(string $type, array $data): void
{
    ensure_enquiry_schema();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO website_enquiries
         (id, enquiry_type, name, email, phone, subject, message, details_json, status, created_at, updated_at)
         VALUES (:id, :type, :name, :email, :phone, :subject, :message, :details, \'pending\', :created_at, :updated_at)',
        [
            'id' => uuid_v4(),
            'type' => $type,
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['mobile'] ?? $data['phone'] ?? '')) ?: null,
            'subject' => trim((string) ($data['subject'] ?? '')) ?: null,
            'message' => trim((string) ($data['message'] ?? '')) ?: null,
            'details' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
}

function controller_contact_enquiry(array $data): void
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
    $subject = trim((string) ($data['subject'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
        json_response(['message' => 'Please enter valid contact details'], 422);
    }
    if ($phone !== '' && strlen($phone) !== 10) {
        json_response(['message' => 'Phone number must contain exactly 10 digits'], 422);
    }
    $data['phone'] = $phone;
    save_website_enquiry('contact', $data);
    json_response(['message' => 'Message received'], 201);
}

function controller_chatbot_enquiry(array $data): void
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
    $message = trim((string) ($data['message'] ?? 'Chatbot callback request'));

    if ($name === '' || strlen($phone) !== 10) {
        json_response(['message' => 'Please enter your name and a valid 10-digit mobile number'], 422);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'Please enter a valid email address'], 422);
    }

    save_website_enquiry('chatbot', [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => 'Chatbot callback request',
        'message' => $message,
        'source' => 'website_chatbot',
    ]);
    json_response(['message' => 'Your details have been shared with our team'], 201);
}

function controller_demo_book(array $data): void
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $mobile = trim((string) ($data['mobile'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $mobile === '') {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $programs = $data['programs'] ?? [];
    $programsText = is_array($programs) && !empty($programs) ? implode(', ', $programs) : 'N/A';
    $gender = trim((string) ($data['gender'] ?? ''));
    // `motherTongue` is retained as a fallback for requests sent by older frontend builds.
    $whatsappNumber = preg_replace('/\D+/', '', (string) ($data['whatsappNumber'] ?? $data['motherTongue'] ?? ''));
    if (strlen($whatsappNumber) !== 10) {
        json_response(['message' => 'WhatsApp number must contain exactly 10 digits'], 422);
    }
    $dob = trim((string) ($data['dob'] ?? ''));
    $messageParts = [
        'Programs: ' . $programsText,
    ];
    if ($gender !== '') {
        $messageParts[] = 'Gender: ' . $gender;
    }
    if ($whatsappNumber !== '') {
        $messageParts[] = 'WhatsApp Number: ' . $whatsappNumber;
    }
    if ($dob !== '') {
        $messageParts[] = 'Date of Birth: ' . $dob;
    }
    $message = implode("\n", $messageParts);

    ensure_demo_booking_schema();
    db_exec_sql(
        'INSERT INTO demo_bookings (name, email, phone, preferred_date, message, status, created_at)
         VALUES (:name, :email, :phone, :preferred_date, :message, \'pending\', :created_at)',
        [
            'name' => $name,
            'email' => $email,
            'phone' => $mobile,
            'preferred_date' => gmdate('Y-m-d'),
            'message' => $message,
            'created_at' => now_sql(),
        ]
    );

    $canNotifyAfterResponse = finish_json_response(['message' => 'Demo request received']);
    $sendEmailSynchronously = strtolower((string) envv('DEMO_SEND_EMAIL_SYNC', 'false')) === 'true';

    if ($canNotifyAfterResponse || $sendEmailSynchronously) {
        try {
            send_plain_mail(
                (string) envv('DEMO_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com'),
                'New Free Demo Request',
                "Free Demo Booking\nName: {$name}\nEmail: {$email}\nMobile: {$mobile}\n{$message}",
                $email,
                $name
            );
        } catch (Throwable $e) {
            error_log('Demo notification failed: ' . $e->getMessage());
        }
    }

    exit;
}

function controller_franchise_apply(array $data): void
{
    $required = ['name', 'email', 'mobile', 'location', 'qualification', 'languages', 'plan'];
    foreach ($required as $field) {
        if (trim((string) ($data[$field] ?? '')) === '') {
            json_response(['message' => 'Invalid request data'], 422);
        }
    }
    if (!filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    save_website_enquiry('franchise', $data);

    send_plain_mail(
        (string) envv('FRANCHISE_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com'),
        'New Franchise Application',
        "Franchise Application\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}\nLocation: {$data['location']}",
        (string) $data['email'],
        (string) $data['name']
    );

    json_response(['message' => 'Application received']);
}

function controller_instructor_apply(array $data): void
{
    $required = ['name', 'email', 'mobile', 'address'];
    foreach ($required as $field) {
        if (trim((string) ($data[$field] ?? '')) === '') {
            json_response(['message' => 'Invalid request data'], 422);
        }
    }
    if (!filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    save_website_enquiry('teacher_training', $data);

    $canNotifyAfterResponse = finish_json_response(['message' => 'Application received'], 201);
    $sendEmailSynchronously = strtolower((string) envv('INSTRUCTOR_SEND_EMAIL_SYNC', 'false')) === 'true';

    if ($canNotifyAfterResponse || $sendEmailSynchronously) {
        try {
            send_plain_mail(
                (string) envv('INSTRUCTOR_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com'),
                'New Instructor Registration',
                "Instructor Application\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}",
                (string) $data['email'],
                (string) $data['name']
            );
        } catch (Throwable $e) {
            error_log('Instructor notification failed: ' . $e->getMessage());
        }
    }

    exit;
}

function public_teacher_course_label(?string $courseType): string
{
    return match ((string) $courseType) {
        'abacus' => 'Abacus',
        'vedic_maths' => 'Vedic Maths',
        default => 'Abacus',
    };
}

function public_teacher_image_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
    $baseUrl = rtrim((string) envv('BASE_URL', get_base_url()), '/');
    if ($path !== '' && str_starts_with($path, '/uploads/')) {
        return $baseUrl . '/backend' . $path;
    }
    if ($path !== '' && str_starts_with($path, '/backend/uploads/')) {
        return $baseUrl . $path;
    }

    return $url;
}

function public_table_exists(string $table): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table',
        ['table' => $table]
    ) > 0;
}

function controller_public_teachers(): void
{
    if (!public_table_exists('teachers')) {
        json_response(['teachers' => []]);
    }

    $rows = db_all(
        "SELECT name, email, phone, expertise, qualification, experience, location, specialization, image, description, joining_date
         FROM teachers
         WHERE status = 'active'
         ORDER BY joining_date DESC, id DESC"
    );

    $teachers = array_map(static function (array $row): array {
        return [
            'name' => (string) ($row['name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'qualification' => (string) (($row['qualification'] ?? '') ?: 'Certified Simple Abacus Instructor'),
            'experience' => (string) (($row['experience'] ?? '') ?: 'Certified Trainer'),
            'location' => (string) (($row['location'] ?? '') ?: 'Online'),
            'specialization' => (string) (($row['specialization'] ?? '') ?: public_teacher_course_label($row['expertise'] ?? null)),
            'image' => public_teacher_image_url($row['image'] ?? ''),
            'description' => (string) (($row['description'] ?? '') ?: 'Simple Abacus teacher ready to guide students with structured practice.'),
            'source' => 'admin_teacher',
        ];
    }, $rows);

    json_response(['teachers' => $teachers]);
}

function controller_payments_webhook(array $data): void
{
    if (function_exists('handle_payment_webhook')) {
        $result = handle_payment_webhook($data);
        json_response([
            'received' => true,
            'event' => [
                'provider' => envv('PAYMENT_PROVIDER', 'razorpay'),
                'receivedAt' => gmdate('c'),
            ],
            'result' => $result,
        ]);
    }

    json_response([
        'received' => true,
        'event' => [
            'provider' => envv('PAYMENT_PROVIDER', 'stripe'),
            'receivedAt' => gmdate('c'),
            'payload' => $data,
            'headers' => [
                'payment-signature' => request_header('payment-signature'),
                'stripe-signature' => request_header('stripe-signature'),
            ],
        ],
    ]);
}
