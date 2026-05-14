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
    $motherTongue = trim((string) ($data['motherTongue'] ?? ''));
    $dob = trim((string) ($data['dob'] ?? ''));
    $messageParts = [
        'Programs: ' . $programsText,
    ];
    if ($gender !== '') {
        $messageParts[] = 'Gender: ' . $gender;
    }
    if ($motherTongue !== '') {
        $messageParts[] = 'Mother Tongue: ' . $motherTongue;
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

    send_plain_mail(
        (string) envv('INSTRUCTOR_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com'),
        'New Instructor Registration',
        "Instructor Application\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}",
        (string) $data['email'],
        (string) $data['name']
    );

    json_response(['message' => 'Application received']);
}

function controller_payments_webhook(array $data): void
{
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
