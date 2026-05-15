<?php

function ensure_training_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS users (
            id CHAR(36) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (function_exists('billing_table_has_column') && !billing_table_has_column('users', 'training_status')) {
        db_exec_sql("ALTER TABLE users ADD COLUMN training_status VARCHAR(30) NOT NULL DEFAULT 'pending'");
        db_exec_sql("UPDATE users SET training_status = 'approved' WHERE role = 'admin'");
    }

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS training_teacher_students (
            id CHAR(36) PRIMARY KEY,
            teacher_user_id CHAR(36) NOT NULL,
            name VARCHAR(255) NOT NULL,
            age INT NULL,
            parent_contact VARCHAR(80) NULL,
            level VARCHAR(80) NULL,
            progress_percent INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_training_students_teacher (teacher_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS teacher_shop_orders (
            id CHAR(36) PRIMARY KEY,
            teacher_user_id CHAR(36) NOT NULL,
            invoice_number VARCHAR(80) NOT NULL UNIQUE,
            product_id VARCHAR(120) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            category VARCHAR(120) NOT NULL,
            selected_option VARCHAR(120) NOT NULL,
            option_label VARCHAR(80) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            final_price DECIMAL(10,2) NOT NULL,
            payment_status VARCHAR(30) NOT NULL DEFAULT "pending",
            payment_url TEXT NULL,
            razorpay_order_id VARCHAR(120) NULL,
            razorpay_payment_id VARCHAR(120) NULL,
            razorpay_signature VARCHAR(255) NULL,
            metadata_json JSON NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_teacher_shop_orders_teacher (teacher_user_id),
            INDEX idx_teacher_shop_orders_status (payment_status),
            INDEX idx_teacher_shop_orders_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (function_exists('billing_table_has_column')) {
        if (!billing_table_has_column('teacher_shop_orders', 'razorpay_order_id')) {
            db_exec_sql('ALTER TABLE teacher_shop_orders ADD COLUMN razorpay_order_id VARCHAR(120) NULL AFTER payment_url');
        }
        if (!billing_table_has_column('teacher_shop_orders', 'razorpay_payment_id')) {
            db_exec_sql('ALTER TABLE teacher_shop_orders ADD COLUMN razorpay_payment_id VARCHAR(120) NULL AFTER razorpay_order_id');
        }
        if (!billing_table_has_column('teacher_shop_orders', 'razorpay_signature')) {
            db_exec_sql('ALTER TABLE teacher_shop_orders ADD COLUMN razorpay_signature VARCHAR(255) NULL AFTER razorpay_payment_id');
        }
    }
}

function training_public_user(array $user): array
{
    $role = (string) ($user['role'] ?? '');
    $status = (string) ($user['training_status'] ?? 'approved');
    return [
        'id' => (string) ($user['id'] ?? ''),
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'role' => $role === 'tutor' ? 'teacher' : $role,
        'approved' => $role !== 'tutor' || $status === 'approved',
        'status' => $role === 'tutor' ? $status : 'approved',
    ];
}

function require_training_teacher(): array
{
    ensure_training_schema();
    $ctx = require_auth();
    if (!in_array((string) $ctx['user']['role'], ['tutor', 'teacher'], true)) {
        json_response(['message' => 'Forbidden'], 403);
    }
    $status = (string) db_value('SELECT training_status FROM users WHERE id = :id LIMIT 1', ['id' => $ctx['user']['id']]);
    if ($status !== 'approved') {
        json_response(['message' => 'Your teacher account is waiting for admin approval.'], 403);
    }
    return $ctx;
}

function controller_training_register(array $data): void
{
    ensure_training_schema();

    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    $requestedRole = (string) ($data['role'] ?? 'teacher');
    $dbRole = $requestedRole === 'teacher' ? 'tutor' : $requestedRole;

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || !in_array($dbRole, ['tutor', 'admin'], true)) {
        json_response(['message' => 'Invalid request data'], 422);
    }

    $exists = db_one('SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($exists) {
        json_response(['message' => 'Email already registered'], 409);
    }

    $pdo = db_conn();
    $userId = uuid_v4();
    $now = now_sql();

    $pdo->beginTransaction();
    try {
        $trainingStatus = $dbRole === 'tutor' ? 'pending' : 'approved';
        db_exec_sql(
            'INSERT INTO users (id, name, email, password, role, training_status, created_at, updated_at)
             VALUES (:id, :name, :email, :password, :role, :training_status, :created_at, :updated_at)',
            [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => $dbRole,
                'training_status' => $trainingStatus,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if ($dbRole === 'tutor') {
            db_exec_sql(
                'INSERT INTO tutors (id, user_id, created_at, updated_at) VALUES (:id, :user_id, :created_at, :updated_at)',
                ['id' => uuid_v4(), 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['message' => 'Failed to register training user'], 500);
    }

    $user = db_one('SELECT id, name, email, role, training_status, created_at, updated_at FROM users WHERE id = :id', ['id' => $userId]);
    if ($dbRole === 'tutor') {
        json_response([
            'message' => 'Registration submitted successfully. Wait for admin approval.',
            'user' => training_public_user($user),
        ], 201);
    }

    json_response(['token' => jwt_create($user), 'user' => training_public_user($user)], 201);
}

function controller_training_login(array $data): void
{
    ensure_training_schema();
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        json_response(['message' => 'Invalid email or password'], 401);
    }

    $user = db_one('SELECT * FROM users WHERE email = :email AND role IN ("tutor", "admin") LIMIT 1', ['email' => $email]);
    if (!$user || !password_verify($password, (string) $user['password'])) {
        json_response(['message' => 'Invalid email or password'], 401);
    }
    if ((string) $user['role'] === 'tutor' && (string) ($user['training_status'] ?? 'approved') !== 'approved') {
        json_response(['message' => 'Your teacher account is waiting for admin approval.'], 403);
    }

    $safe = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'training_status' => $user['training_status'] ?? 'approved',
        'created_at' => $user['created_at'] ?? null,
        'updated_at' => $user['updated_at'] ?? null,
    ];

    json_response(['token' => jwt_create($safe), 'user' => training_public_user($safe)]);
}

function controller_training_me(array $ctx): void
{
    ensure_training_schema();
    $user = db_one(
        'SELECT id, name, email, role, training_status FROM users WHERE id = :id LIMIT 1',
        ['id' => $ctx['user']['id']]
    );
    if (!$user) {
        json_response(['message' => 'Unauthorized'], 401);
    }
    if ((string) $user['role'] === 'tutor' && (string) ($user['training_status'] ?? 'approved') !== 'approved') {
        json_response(['message' => 'Your teacher account is waiting for admin approval.'], 403);
    }
    json_response(['user' => training_public_user($user)]);
}

function controller_training_teacher_dashboard(array $ctx): void
{
    ensure_training_schema();
    $teacherId = (string) $ctx['user']['id'];
    $students = db_all(
        'SELECT id AS _id, name, age, parent_contact AS parentContact, level, progress_percent AS progressPercent
         FROM training_teacher_students
         WHERE teacher_user_id = :teacher_user_id
         ORDER BY created_at DESC',
        ['teacher_user_id' => $teacherId]
    );

    json_response([
        'progress' => ['percent' => 0, 'completedModules' => 0, 'totalModules' => 10],
        'students' => $students,
        'assignments' => [],
        'materials' => [],
    ]);
}

function controller_training_teacher_add_student(array $ctx, array $data): void
{
    ensure_training_schema();
    $name = trim((string) ($data['name'] ?? ''));
    $age = (int) ($data['age'] ?? 0);
    $parentContact = trim((string) ($data['parentContact'] ?? ''));
    $level = trim((string) ($data['level'] ?? ''));

    if ($name === '' || $age <= 0) {
        json_response(['message' => 'Please enter a valid student name and age'], 422);
    }

    $now = now_sql();
    db_exec_sql(
        'INSERT INTO training_teacher_students
         (id, teacher_user_id, name, age, parent_contact, level, progress_percent, created_at, updated_at)
         VALUES (:id, :teacher_user_id, :name, :age, :parent_contact, :level, 0, :created_at, :updated_at)',
        [
            'id' => uuid_v4(),
            'teacher_user_id' => (string) $ctx['user']['id'],
            'name' => $name,
            'age' => $age,
            'parent_contact' => $parentContact,
            'level' => $level,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    json_response(['message' => 'Student added'], 201);
}

function training_shop_order_payload(array $row): array
{
    return [
        'id' => (string) $row['id'],
        'invoiceNumber' => (string) $row['invoice_number'],
        'productId' => (string) $row['product_id'],
        'productName' => (string) $row['product_name'],
        'category' => (string) $row['category'],
        'selectedOption' => (string) $row['selected_option'],
        'optionLabel' => (string) $row['option_label'],
        'quantity' => (int) $row['quantity'],
        'unitPrice' => (float) $row['unit_price'],
        'finalPrice' => (float) $row['final_price'],
        'paymentStatus' => (string) $row['payment_status'],
        'paymentUrl' => $row['payment_url'] ?? null,
        'razorpayOrderId' => $row['razorpay_order_id'] ?? null,
        'razorpayPaymentId' => $row['razorpay_payment_id'] ?? null,
        'createdAt' => (string) $row['created_at'],
    ];
}

function controller_training_teacher_shop_orders(array $ctx): void
{
    ensure_training_schema();
    $rows = db_all(
        'SELECT * FROM teacher_shop_orders WHERE teacher_user_id = :teacher_user_id ORDER BY created_at DESC',
        ['teacher_user_id' => (string) $ctx['user']['id']]
    );
    json_response(['orders' => array_map('training_shop_order_payload', $rows)]);
}

function controller_training_teacher_shop_create_order(array $ctx, array $data): void
{
    ensure_training_schema();

    $productId = trim((string) ($data['productId'] ?? ''));
    $productName = trim((string) ($data['productName'] ?? ''));
    $category = trim((string) ($data['category'] ?? ''));
    $selectedOption = trim((string) ($data['selectedOption'] ?? ''));
    $optionLabel = trim((string) ($data['optionLabel'] ?? 'Option'));
    $quantity = (int) ($data['quantity'] ?? 0);
    $unitPrice = (float) ($data['unitPrice'] ?? 0);
    $finalPrice = (float) ($data['finalPrice'] ?? 0);

    if ($productId === '' || $productName === '' || $selectedOption === '' || $quantity < 1 || $unitPrice <= 0 || $finalPrice <= 0) {
        json_response(['message' => 'Invalid order details'], 422);
    }
    if (abs(($unitPrice * $quantity) - $finalPrice) > 0.01) {
        json_response(['message' => 'Order total mismatch'], 422);
    }

    $orderId = uuid_v4();
    $invoiceNumber = 'TS-' . gmdate('Ymd') . '-' . strtoupper(substr(str_replace('-', '', $orderId), 0, 8));
    $origin = rtrim((string) (request_header('Origin') ?: envv('FRONTEND_URL', get_base_url())), '/');
    $paymentUrl = $origin . '/training/payment-gateway?orderId=' . rawurlencode($orderId);
    $now = now_sql();

    db_exec_sql(
        'INSERT INTO teacher_shop_orders
         (id, teacher_user_id, invoice_number, product_id, product_name, category, selected_option, option_label, quantity, unit_price, final_price, payment_status, payment_url, metadata_json, created_at, updated_at)
         VALUES
         (:id, :teacher_user_id, :invoice_number, :product_id, :product_name, :category, :selected_option, :option_label, :quantity, :unit_price, :final_price, :payment_status, :payment_url, :metadata_json, :created_at, :updated_at)',
        [
            'id' => $orderId,
            'teacher_user_id' => (string) $ctx['user']['id'],
            'invoice_number' => $invoiceNumber,
            'product_id' => $productId,
            'product_name' => $productName,
            'category' => $category,
            'selected_option' => $selectedOption,
            'option_label' => $optionLabel,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'final_price' => $finalPrice,
            'payment_status' => 'pending',
            'payment_url' => $paymentUrl,
            'metadata_json' => json_encode(['source' => 'teacher_dashboard_shop'], JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $row = db_one('SELECT * FROM teacher_shop_orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    json_response(['order' => training_shop_order_payload($row), 'paymentUrl' => $paymentUrl], 201);
}

function controller_training_teacher_shop_pay_order(array $ctx, string $orderId): void
{
    ensure_training_schema();
    $orderId = trim($orderId);
    if ($orderId === '') {
        json_response(['message' => 'Invalid order'], 422);
    }

    $row = db_one(
        'SELECT * FROM teacher_shop_orders WHERE id = :id AND teacher_user_id = :teacher_user_id LIMIT 1',
        ['id' => $orderId, 'teacher_user_id' => (string) $ctx['user']['id']]
    );
    if (!$row) {
        json_response(['message' => 'Order not found'], 404);
    }

    if (!function_exists('get_payment_gateway_config') || !function_exists('razorpay_request')) {
        json_response(['message' => 'Razorpay integration is unavailable'], 500);
    }

    $gateway = get_payment_gateway_config('razorpay');
    if (empty($gateway['enabled']) || empty($gateway['key_id']) || empty($gateway['secret'])) {
        json_response(['message' => 'Razorpay is not configured. Please contact admin.'], 400);
    }

    $existingOrderId = (string) ($row['razorpay_order_id'] ?? '');
    $rzOrder = null;
    if ($existingOrderId === '') {
        $orderResp = razorpay_request('POST', '/orders', [
            'amount' => (int) round(((float) $row['final_price']) * 100),
            'currency' => 'INR',
            'receipt' => (string) $row['invoice_number'],
            'notes' => [
                'teacher_shop_order_id' => $orderId,
                'invoice_number' => (string) $row['invoice_number'],
                'product_name' => (string) $row['product_name'],
            ],
        ], $gateway['key_id'], $gateway['secret']);

        if (!($orderResp['ok'] ?? false)) {
            json_response(['message' => (string) ($orderResp['message'] ?? 'Failed to create Razorpay order')], 502);
        }
        $rzOrder = (array) ($orderResp['data'] ?? []);
        $existingOrderId = (string) ($rzOrder['id'] ?? '');

        db_exec_sql(
            'UPDATE teacher_shop_orders
             SET razorpay_order_id = :razorpay_order_id, updated_at = :updated_at
             WHERE id = :id AND teacher_user_id = :teacher_user_id',
            [
                'razorpay_order_id' => $existingOrderId,
                'updated_at' => now_sql(),
                'id' => $orderId,
                'teacher_user_id' => (string) $ctx['user']['id'],
            ]
        );
    }

    $updated = db_one('SELECT * FROM teacher_shop_orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    json_response([
        'order' => training_shop_order_payload($updated),
        'keyId' => (string) $gateway['key_id'],
        'razorpayOrder' => [
            'id' => $existingOrderId,
            'amount' => (int) round(((float) $row['final_price']) * 100),
            'currency' => 'INR',
        ],
    ]);
}

function controller_training_teacher_shop_verify_order(array $ctx, string $orderId, array $data): void
{
    ensure_training_schema();
    $orderId = trim($orderId);
    $razorpayOrderId = trim((string) ($data['razorpayOrderId'] ?? ''));
    $paymentId = trim((string) ($data['razorpayPaymentId'] ?? ''));
    $signature = trim((string) ($data['razorpaySignature'] ?? ''));

    if ($orderId === '' || $razorpayOrderId === '' || $paymentId === '' || $signature === '') {
        json_response(['message' => 'Invalid payment verification payload'], 422);
    }

    $row = db_one(
        'SELECT * FROM teacher_shop_orders WHERE id = :id AND teacher_user_id = :teacher_user_id LIMIT 1',
        ['id' => $orderId, 'teacher_user_id' => (string) $ctx['user']['id']]
    );
    if (!$row) {
        json_response(['message' => 'Order not found'], 404);
    }
    if ((string) ($row['razorpay_order_id'] ?? '') !== $razorpayOrderId) {
        json_response(['message' => 'Payment order mismatch'], 422);
    }

    $gateway = get_payment_gateway_config('razorpay');
    if (empty($gateway['secret'])) {
        json_response(['message' => 'Razorpay secret is not configured'], 500);
    }

    $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, (string) $gateway['secret']);
    if (!hash_equals($expected, $signature)) {
        db_exec_sql(
            'UPDATE teacher_shop_orders SET payment_status = :payment_status, updated_at = :updated_at WHERE id = :id',
            ['payment_status' => 'failed', 'updated_at' => now_sql(), 'id' => $orderId]
        );
        json_response(['message' => 'Payment signature verification failed'], 422);
    }

    db_exec_sql(
        'UPDATE teacher_shop_orders
         SET payment_status = :payment_status, razorpay_payment_id = :razorpay_payment_id, razorpay_signature = :razorpay_signature, updated_at = :updated_at
         WHERE id = :id AND teacher_user_id = :teacher_user_id',
        [
            'payment_status' => 'successful',
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
            'updated_at' => now_sql(),
            'id' => $orderId,
            'teacher_user_id' => (string) $ctx['user']['id'],
        ]
    );

    $updated = db_one('SELECT * FROM teacher_shop_orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    json_response(['order' => training_shop_order_payload($updated)]);
}

function controller_training_admin_teachers(): void
{
    ensure_training_schema();
    $teachers = db_all('SELECT id, name, email, role, training_status FROM users WHERE role = "tutor" ORDER BY FIELD(training_status, "pending", "approved", "rejected"), created_at DESC');
    json_response(['teachers' => array_map('training_public_user', $teachers)]);
}

function controller_training_admin_approve_teacher(string $teacherId): void
{
    ensure_training_schema();
    $teacherId = trim($teacherId);
    if ($teacherId === '') {
        json_response(['message' => 'Invalid teacher'], 422);
    }

    $teacher = db_one('SELECT id FROM users WHERE id = :id AND role = "tutor" LIMIT 1', ['id' => $teacherId]);
    if (!$teacher) {
        json_response(['message' => 'Teacher not found'], 404);
    }

    db_exec_sql(
        'UPDATE users SET training_status = :training_status, updated_at = :updated_at WHERE id = :id AND role = "tutor"',
        ['training_status' => 'approved', 'updated_at' => now_sql(), 'id' => $teacherId]
    );

    json_response(['message' => 'Teacher approved']);
}

function controller_training_admin_students(): void
{
    ensure_training_schema();
    $students = db_all('SELECT id AS _id, name, age, parent_contact AS parentContact, level FROM training_teacher_students ORDER BY created_at DESC');
    json_response(['students' => $students]);
}
