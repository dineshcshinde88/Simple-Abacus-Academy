<?php

function send_plain_mail(string $to, string $subject, string $body): void
{
    $from = (string) envv('MAIL_FROM_ADDRESS', 'no-reply@simpleabacus.com');
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . phpversion(),
    ];
    @mail($to, $subject, $body, implode("\r\n", $headers));
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
    send_plain_mail(
        (string) envv('DEMO_NOTIFICATION_EMAIL', 'simpleabacuspune@gmail.com'),
        'New Free Demo Request',
        "Free Demo Booking\nName: {$name}\nEmail: {$email}\nMobile: {$mobile}\nPrograms: {$programsText}"
    );

    json_response(['message' => 'Demo request received']);
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
        "Franchise Application\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}\nLocation: {$data['location']}"
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
        "Instructor Application\nName: {$data['name']}\nEmail: {$data['email']}\nMobile: {$data['mobile']}"
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

