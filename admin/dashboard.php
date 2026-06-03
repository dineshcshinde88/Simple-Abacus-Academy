<?php
$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/includes/header.php';

function admin_dashboard_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_dashboard_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_dashboard_database_name(PDO $pdo): string
{
    return (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
}

function admin_dashboard_env_value(string $path, string $key): string
{
    if (!is_file($path)) {
        return '';
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return '';
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$lineKey, $value] = explode('=', $line, 2);
        if (trim($lineKey) !== $key) {
            continue;
        }

        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }

    return '';
}

function admin_dashboard_backend_pdo(PDO $adminPdo): ?PDO
{
    $databaseUrl = admin_dashboard_env_value(__DIR__ . '/../backend/.env', 'DATABASE_URL');
    if ($databaseUrl === '') {
        $databaseUrl = admin_dashboard_env_value(__DIR__ . '/../.env', 'DATABASE_URL');
    }
    if ($databaseUrl === '') {
        return null;
    }

    $parts = parse_url($databaseUrl);
    if (!is_array($parts)) {
        return null;
    }

    $host = (string) ($parts['host'] ?? 'localhost');
    $port = (string) ($parts['port'] ?? '3306');
    $db = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';
    $user = isset($parts['user']) ? urldecode((string) $parts['user']) : '';
    $pass = isset($parts['pass']) ? urldecode((string) $parts['pass']) : '';

    if ($db === '' || $user === '') {
        return null;
    }

    if ($db === admin_dashboard_database_name($adminPdo)) {
        return $adminPdo;
    }

    try {
        return new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        return null;
    }
}

function admin_dashboard_count(PDO $pdo, string $sql): int
{
    try {
        return (int) $pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function admin_dashboard_rows(PDO $pdo, string $sql): array
{
    try {
        $rows = $pdo->query($sql)->fetchAll();
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

function admin_dashboard_format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    $ts = strtotime($value);
    return $ts ? date('d M Y, h:i A', $ts) : $value;
}

function admin_competition_table_has_column(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function admin_competition_ensure_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS categories (
        id CHAR(36) PRIMARY KEY,
        name VARCHAR(180) NOT NULL,
        slug VARCHAR(180) NOT NULL UNIQUE,
        description TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS subcategories (
        id CHAR(36) PRIMARY KEY,
        category_id CHAR(36) NOT NULL,
        name VARCHAR(180) NOT NULL,
        slug VARCHAR(180) NOT NULL,
        description TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uniq_subcategories_category_slug (category_id, slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS competition_users (
        id CHAR(36) PRIMARY KEY,
        name VARCHAR(180) NOT NULL,
        email VARCHAR(180) NOT NULL UNIQUE,
        mobile VARCHAR(30) NULL,
        city VARCHAR(120) NULL,
        school VARCHAR(180) NULL,
        gender VARCHAR(30) NULL,
        date_of_birth DATE NULL,
        maats_category VARCHAR(120) NULL,
        maats_subcategory VARCHAR(120) NULL,
        calculus_grade VARCHAR(120) NULL,
        street_address TEXT NULL,
        state VARCHAR(120) NULL,
        pin_code VARCHAR(20) NULL,
        country VARCHAR(120) NULL,
        password VARCHAR(255) NULL,
        status VARCHAR(20) NOT NULL DEFAULT "pending",
        approved_at DATETIME NULL,
        approved_by CHAR(36) NULL,
        credentials_sent_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $columns = [
        'gender' => 'VARCHAR(30) NULL',
        'date_of_birth' => 'DATE NULL',
        'maats_category' => 'VARCHAR(120) NULL',
        'maats_subcategory' => 'VARCHAR(120) NULL',
        'calculus_grade' => 'VARCHAR(120) NULL',
        'street_address' => 'TEXT NULL',
        'state' => 'VARCHAR(120) NULL',
        'pin_code' => 'VARCHAR(20) NULL',
        'country' => 'VARCHAR(120) NULL',
    ];
    foreach ($columns as $column => $definition) {
        if (!admin_competition_table_has_column($pdo, 'competition_users', $column)) {
            $pdo->exec("ALTER TABLE competition_users ADD COLUMN {$column} {$definition}");
        }
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS competitions (
        id CHAR(36) PRIMARY KEY,
        category_id CHAR(36) NULL,
        subcategory_id CHAR(36) NULL,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT NULL,
        duration_minutes INT NOT NULL DEFAULT 30,
        total_questions INT NOT NULL DEFAULT 0,
        negative_marking DECIMAL(6,2) NOT NULL DEFAULT 0,
        fee DECIMAL(10,2) NOT NULL DEFAULT 0,
        currency VARCHAR(10) NOT NULL DEFAULT "INR",
        status VARCHAR(20) NOT NULL DEFAULT "scheduled",
        results_published TINYINT(1) NOT NULL DEFAULT 0,
        created_by CHAR(36) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS competition_schedule (
        id CHAR(36) PRIMARY KEY,
        competition_id CHAR(36) NOT NULL UNIQUE,
        competition_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NOT NULL,
        timezone VARCHAR(80) NOT NULL DEFAULT "Asia/Kolkata",
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS competition_registrations (
        id CHAR(36) PRIMARY KEY,
        competition_user_id CHAR(36) NOT NULL,
        competition_id CHAR(36) NOT NULL,
        payment_status VARCHAR(20) NOT NULL DEFAULT "paid",
        access_status VARCHAR(20) NOT NULL DEFAULT "active",
        registered_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uniq_competition_registration (competition_user_id, competition_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS practice_kits (
        id CHAR(36) PRIMARY KEY,
        competition_id CHAR(36) NULL,
        category_id CHAR(36) NULL,
        subcategory_id CHAR(36) NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        validity_days INT NOT NULL DEFAULT 90,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS practice_kit_access (
        id CHAR(36) PRIMARY KEY,
        competition_user_id CHAR(36) NOT NULL,
        practice_kit_id CHAR(36) NOT NULL,
        competition_id CHAR(36) NULL,
        start_date DATETIME NOT NULL,
        expiry_date DATETIME NOT NULL,
        access_status VARCHAR(20) NOT NULL DEFAULT "active",
        source VARCHAR(50) NOT NULL DEFAULT "admin",
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uniq_practice_kit_access_user_kit (competition_user_id, practice_kit_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function admin_competition_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function admin_competition_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: 'item';
    return trim($slug, '-');
}

function admin_competition_seed_default_categories(PDO $pdo): void
{
    $defaults = [
        'KG' => ['KG1', 'KG2'],
        'A' => ['A1', 'A2', 'A3', 'A4'],
        'B' => ['B1', 'B2', 'B3', 'B4'],
        'C' => ['C1', 'C2', 'C3', 'C4'],
        'D' => ['D1', 'D2', 'D3', 'D4'],
        'E' => ['E1', 'E2', 'E3', 'E4'],
        'F' => ['F1', 'F2', 'F3', 'F4'],
        'G' => ['G1', 'G2', 'G3', 'G4'],
        'H' => ['H1', 'H2', 'H3', 'H4'],
        'I' => ['I1', 'I2', 'I3', 'I4'],
        'J' => ['J1', 'J2', 'J3', 'J4'],
        'K' => ['K1', 'K2', 'K3', 'K4'],
        'L' => ['L1', 'L2', 'L3', 'L4'],
        'M' => ['M1', 'M2', 'M3', 'M4'],
        'N' => ['N1', 'N2', 'N3', 'N4'],
        'O' => ['O1', 'O2', 'O3', 'O4'],
        'P' => ['P1', 'P2', 'P3', 'P4'],
    ];

    foreach ($defaults as $categoryName => $subcategories) {
        $category = admin_competition_get_or_create_category($pdo, $categoryName);
        if (!$category || empty($category['id'])) {
            continue;
        }
        foreach ($subcategories as $subcategoryName) {
            admin_competition_get_or_create_subcategory($pdo, (string) $category['id'], $subcategoryName);
        }
    }
}

function admin_competition_default_category_rows(): array
{
    return [
        ['category_name' => 'KG', 'subcategory_names' => 'KG1, KG2'],
        ['category_name' => 'A', 'subcategory_names' => 'A1, A2, A3, A4'],
        ['category_name' => 'B', 'subcategory_names' => 'B1, B2, B3, B4'],
        ['category_name' => 'C', 'subcategory_names' => 'C1, C2, C3, C4'],
        ['category_name' => 'D', 'subcategory_names' => 'D1, D2, D3, D4'],
        ['category_name' => 'E', 'subcategory_names' => 'E1, E2, E3, E4'],
        ['category_name' => 'F', 'subcategory_names' => 'F1, F2, F3, F4'],
        ['category_name' => 'G', 'subcategory_names' => 'G1, G2, G3, G4'],
        ['category_name' => 'H', 'subcategory_names' => 'H1, H2, H3, H4'],
        ['category_name' => 'I', 'subcategory_names' => 'I1, I2, I3, I4'],
        ['category_name' => 'J', 'subcategory_names' => 'J1, J2, J3, J4'],
        ['category_name' => 'K', 'subcategory_names' => 'K1, K2, K3, K4'],
        ['category_name' => 'L', 'subcategory_names' => 'L1, L2, L3, L4'],
        ['category_name' => 'M', 'subcategory_names' => 'M1, M2, M3, M4'],
        ['category_name' => 'N', 'subcategory_names' => 'N1, N2, N3, N4'],
        ['category_name' => 'O', 'subcategory_names' => 'O1, O2, O3, O4'],
        ['category_name' => 'P', 'subcategory_names' => 'P1, P2, P3, P4'],
    ];
}

function admin_competition_get_or_create_category(PDO $pdo, string $name): ?array
{
    $name = trim($name);
    if ($name === '') return null;
    $row = admin_dashboard_rows($pdo, 'SELECT * FROM categories WHERE name = ' . $pdo->quote($name) . ' LIMIT 1');
    if (!empty($row)) return $row[0];
    $id = admin_competition_uuid();
    $now = gmdate('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO categories (id, name, slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$id, $name, admin_competition_slug($name) . '-' . substr($id, 0, 6), $now, $now]);
    return ['id' => $id, 'name' => $name];
}

function admin_competition_get_or_create_subcategory(PDO $pdo, string $categoryId, string $name): ?array
{
    $name = trim($name);
    if ($categoryId === '' || $name === '') return null;
    $stmt = $pdo->prepare('SELECT * FROM subcategories WHERE category_id = ? AND name = ? LIMIT 1');
    $stmt->execute([$categoryId, $name]);
    $row = $stmt->fetch();
    if ($row) return $row;
    $id = admin_competition_uuid();
    $now = gmdate('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO subcategories (id, category_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $categoryId, $name, admin_competition_slug($name) . '-' . substr($id, 0, 6), $now, $now]);
    return ['id' => $id, 'name' => $name];
}

$backendPdo = admin_dashboard_backend_pdo($pdo);
if ($backendPdo) {
    admin_competition_ensure_schema($backendPdo);
    admin_competition_seed_default_categories($backendPdo);
}

$competitionMessage = '';
if ($backendPdo && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'provide_competition_details') {
    try {
        $studentId = trim((string) ($_POST['competition_user_id'] ?? ''));
        $password = trim((string) ($_POST['login_password'] ?? ''));
        $categoryName = trim((string) ($_POST['category'] ?? ''));
        $subcategoryName = trim((string) ($_POST['subcategory'] ?? ''));
        $kitTitle = trim((string) ($_POST['kit_title'] ?? 'Practice Kit'));
        $competitionTitle = trim((string) ($_POST['competition_title'] ?? 'Online Competition'));
        $competitionDate = trim((string) ($_POST['competition_date'] ?? ''));
        $startTime = trim((string) ($_POST['start_time'] ?? ''));
        $endTime = trim((string) ($_POST['end_time'] ?? ''));

        if ($studentId === '' || $password === '' || $categoryName === '' || $subcategoryName === '' || $competitionDate === '' || $startTime === '' || $endTime === '') {
            throw new RuntimeException('Please fill login password, category, subcategory, date, start time, and end time.');
        }

        $backendPdo->beginTransaction();
        $now = gmdate('Y-m-d H:i:s');
        $category = admin_competition_get_or_create_category($backendPdo, $categoryName);
        $subcategory = $category ? admin_competition_get_or_create_subcategory($backendPdo, $category['id'], $subcategoryName) : null;

        $competitionId = admin_competition_uuid();
        $competitionSlug = admin_competition_slug($competitionTitle) . '-' . substr($competitionId, 0, 6);
        $stmt = $backendPdo->prepare(
            'INSERT INTO competitions (id, category_id, subcategory_id, title, slug, duration_minutes, total_questions, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 3, 60, "scheduled", ?, ?, ?)'
        );
        $stmt->execute([$competitionId, $category['id'] ?? null, $subcategory['id'] ?? null, $competitionTitle, $competitionSlug, $_SESSION['admin_id'] ?? null, $now, $now]);

        $stmt = $backendPdo->prepare(
            'INSERT INTO competition_schedule (id, competition_id, competition_date, start_time, end_time, starts_at, ends_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            admin_competition_uuid(),
            $competitionId,
            $competitionDate,
            $startTime,
            $endTime,
            $competitionDate . ' ' . $startTime,
            $competitionDate . ' ' . $endTime,
            $now,
            $now,
        ]);

        $kitId = admin_competition_uuid();
        $stmt = $backendPdo->prepare(
            'INSERT INTO practice_kits (id, competition_id, category_id, subcategory_id, title, description, validity_days, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 90, ?, ?)'
        );
        $stmt->execute([$kitId, $competitionId, $category['id'] ?? null, $subcategory['id'] ?? null, $kitTitle, 'Admin assigned 90 days practice kit', $now, $now]);

        $expiry = gmdate('Y-m-d H:i:s', strtotime($now . ' +90 days'));
        $stmt = $backendPdo->prepare(
            'INSERT INTO practice_kit_access (id, competition_user_id, practice_kit_id, competition_id, start_date, expiry_date, access_status, source, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, "active", "admin", ?, ?)
             ON DUPLICATE KEY UPDATE competition_id = VALUES(competition_id), start_date = VALUES(start_date), expiry_date = VALUES(expiry_date), access_status = "active", updated_at = VALUES(updated_at)'
        );
        $stmt->execute([admin_competition_uuid(), $studentId, $kitId, $competitionId, $now, $expiry, $now, $now]);

        $stmt = $backendPdo->prepare(
            'INSERT INTO competition_registrations (id, competition_user_id, competition_id, payment_status, access_status, registered_at, created_at, updated_at)
             VALUES (?, ?, ?, "paid", "active", ?, ?, ?)
             ON DUPLICATE KEY UPDATE payment_status = "paid", access_status = "active", updated_at = VALUES(updated_at)'
        );
        $stmt->execute([admin_competition_uuid(), $studentId, $competitionId, $now, $now, $now]);

        $stmt = $backendPdo->prepare(
            'UPDATE competition_users
             SET password = ?, status = "approved", approved_at = COALESCE(approved_at, ?), credentials_sent_at = ?,
                 maats_category = ?, maats_subcategory = ?, updated_at = ?
             WHERE id = ?'
        );
        $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $now, $now, $categoryName, $subcategoryName, $now, $studentId]);

        $backendPdo->commit();
        $competitionMessage = 'Competition access details saved successfully.';
    } catch (Throwable $e) {
        if ($backendPdo->inTransaction()) {
            $backendPdo->rollBack();
        }
        $competitionMessage = $e->getMessage();
    }
}

if ($backendPdo && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'schedule_online_competition') {
    try {
        $title = trim((string) ($_POST['schedule_title'] ?? ''));
        $categoryName = trim((string) ($_POST['schedule_category'] ?? ''));
        $subcategoryName = trim((string) ($_POST['schedule_subcategory'] ?? ''));
        $date = trim((string) ($_POST['schedule_date'] ?? ''));
        $startTime = trim((string) ($_POST['schedule_start_time'] ?? ''));
        $endTime = trim((string) ($_POST['schedule_end_time'] ?? ''));
        $duration = max(1, (int) ($_POST['schedule_duration'] ?? 3));
        $questions = max(0, (int) ($_POST['schedule_questions'] ?? 60));
        $fee = max(0, (float) ($_POST['schedule_fee'] ?? 0));

        if ($title === '' || $categoryName === '' || $subcategoryName === '' || $date === '' || $startTime === '' || $endTime === '') {
            throw new RuntimeException('Please fill title, category, subcategory, date, start time, and end time.');
        }

        $backendPdo->beginTransaction();
        $now = gmdate('Y-m-d H:i:s');
        $category = admin_competition_get_or_create_category($backendPdo, $categoryName);
        $subcategory = $category ? admin_competition_get_or_create_subcategory($backendPdo, $category['id'], $subcategoryName) : null;
        $competitionId = admin_competition_uuid();

        $stmt = $backendPdo->prepare(
            'INSERT INTO competitions
             (id, category_id, subcategory_id, title, slug, description, duration_minutes, total_questions, fee, currency, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "INR", "scheduled", ?, ?, ?)'
        );
        $stmt->execute([
            $competitionId,
            $category['id'] ?? null,
            $subcategory['id'] ?? null,
            $title,
            admin_competition_slug($title) . '-' . substr($competitionId, 0, 6),
            trim((string) ($_POST['schedule_description'] ?? '')),
            $duration,
            $questions,
            $fee,
            $_SESSION['admin_id'] ?? null,
            $now,
            $now,
        ]);

        $stmt = $backendPdo->prepare(
            'INSERT INTO competition_schedule (id, competition_id, competition_date, start_time, end_time, starts_at, ends_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            admin_competition_uuid(),
            $competitionId,
            $date,
            $startTime,
            $endTime,
            $date . ' ' . $startTime,
            $date . ' ' . $endTime,
            $now,
            $now,
        ]);

        $backendPdo->commit();
        $competitionMessage = 'Online competition scheduled successfully.';
    } catch (Throwable $e) {
        if ($backendPdo->inTransaction()) {
            $backendPdo->rollBack();
        }
        $competitionMessage = $e->getMessage();
    }
}

$hasLegacySubscriptions = admin_dashboard_table_exists($pdo, 'subscriptions');
$hasAppStudents = $backendPdo
  && admin_dashboard_table_exists($backendPdo, 'users')
  && admin_dashboard_table_exists($backendPdo, 'students');
$hasAppSubscriptions = $backendPdo
  && admin_dashboard_table_exists($backendPdo, 'student_subscriptions')
  && admin_dashboard_table_exists($backendPdo, 'users')
  && admin_dashboard_table_exists($backendPdo, 'students');
$hasAppDemoBookings = $backendPdo && admin_dashboard_table_exists($backendPdo, 'demo_bookings');
$hasAppInstructors = $backendPdo && admin_dashboard_table_exists($backendPdo, 'instructors');

$legacyStudents = admin_dashboard_count($pdo, 'SELECT COUNT(*) FROM students');
$appStudents = $hasAppStudents ? admin_dashboard_count($backendPdo, "SELECT COUNT(*) FROM users WHERE role = 'student'") : 0;
$totalStudents = $legacyStudents + $appStudents;
$totalSubscriptions = ($hasLegacySubscriptions ? admin_dashboard_count($pdo, "SELECT COUNT(*) FROM subscriptions WHERE payment_status = 'paid'") : 0)
  + ($hasAppSubscriptions ? admin_dashboard_count($backendPdo, "SELECT COUNT(*) FROM student_subscriptions WHERE payment_status = 'paid'") : 0);
$totalUnpaidSubscriptions = ($hasLegacySubscriptions ? admin_dashboard_count($pdo, "SELECT COUNT(*) FROM subscriptions WHERE payment_status IN ('unpaid', 'pending')") : 0)
  + ($hasAppSubscriptions ? admin_dashboard_count($backendPdo, "SELECT COUNT(*) FROM student_subscriptions WHERE payment_status IN ('unpaid', 'pending')") : 0);
$totalDemos = (int) $pdo->query('SELECT COUNT(*) FROM demo_bookings')->fetchColumn()
  + ($hasAppDemoBookings ? admin_dashboard_count($backendPdo, 'SELECT COUNT(*) FROM demo_bookings') : 0);
$approvedInstructors = $hasAppInstructors ? admin_dashboard_count($backendPdo, "SELECT COUNT(*) FROM instructors WHERE status = 'approved' AND is_verified = 1") : 0;
$totalTeachers = (int) $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn() + $approvedInstructors;
$pendingInstructors = $hasAppInstructors ? admin_dashboard_count($backendPdo, "SELECT COUNT(*) FROM instructors WHERE status = 'pending'") : 0;
$hasCompetitionUsers = $backendPdo && admin_dashboard_table_exists($backendPdo, 'competition_users');
$competitionRegistrations = $hasCompetitionUsers ? admin_dashboard_rows($backendPdo, "
    SELECT cu.*,
      pka.start_date AS kit_start_date,
      pka.expiry_date AS kit_expiry_date,
      pka.access_status AS kit_status,
      pk.title AS kit_title,
      c.title AS competition_title,
      cs.competition_date,
      cs.start_time,
      cs.end_time
    FROM competition_users cu
    LEFT JOIN practice_kit_access pka ON pka.competition_user_id = cu.id
    LEFT JOIN practice_kits pk ON pk.id = pka.practice_kit_id
    LEFT JOIN competitions c ON c.id = pka.competition_id
    LEFT JOIN competition_schedule cs ON cs.competition_id = c.id
    ORDER BY cu.created_at DESC
") : [];
$pendingCompetitionCount = count(array_filter($competitionRegistrations, static fn(array $row): bool => ($row['status'] ?? '') === 'pending'));
$approvedCompetitionCount = count(array_filter($competitionRegistrations, static fn(array $row): bool => ($row['status'] ?? '') === 'approved'));
$scheduledCompetitions = $backendPdo && admin_dashboard_table_exists($backendPdo, 'competitions') ? admin_dashboard_rows($backendPdo, "
    SELECT c.*, cat.name AS category_name, sub.name AS subcategory_name, cs.competition_date, cs.start_time, cs.end_time
    FROM competitions c
    LEFT JOIN categories cat ON cat.id = c.category_id
    LEFT JOIN subcategories sub ON sub.id = c.subcategory_id
    LEFT JOIN competition_schedule cs ON cs.competition_id = c.id
    ORDER BY COALESCE(cs.starts_at, c.created_at) DESC
") : [];
$competitionCategoryRows = $backendPdo
  && admin_dashboard_table_exists($backendPdo, 'categories')
  && admin_dashboard_table_exists($backendPdo, 'subcategories')
    ? admin_dashboard_rows($backendPdo, "
        SELECT c.id, c.name AS category_name, GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS subcategory_names
        FROM categories c
        LEFT JOIN subcategories s ON s.category_id = c.id
        WHERE c.name IN ('KG', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P')
        GROUP BY c.id, c.name
        ORDER BY FIELD(c.name, 'KG', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'), c.name
    ")
    : [];
if (empty($competitionCategoryRows)) {
    $competitionCategoryRows = admin_competition_default_category_rows();
}

?>
<div class="row g-4 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Total Students</div>
      <div class="fs-3 fw-bold"><?php echo $totalStudents; ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Paid Fees</div>
      <div class="fs-3 fw-bold"><?php echo $totalSubscriptions; ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Unpaid Fees</div>
      <div class="fs-3 fw-bold"><?php echo $totalUnpaidSubscriptions; ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Demo Bookings</div>
      <div class="fs-3 fw-bold"><?php echo $totalDemos; ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Teachers</div>
      <div class="fs-3 fw-bold"><?php echo $totalTeachers; ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <a class="card card-metric p-3 text-decoration-none text-reset d-block" href="instructors.php">
      <div class="text-muted small">Pending Tutor Approvals</div>
      <div class="fs-3 fw-bold"><?php echo $pendingInstructors; ?></div>
    </a>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Online Competition Registrations</div>
      <div class="fs-3 fw-bold"><?php echo count($competitionRegistrations); ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Pending Competition Approvals</div>
      <div class="fs-3 fw-bold"><?php echo $pendingCompetitionCount; ?></div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4" id="online-competition-admin">
  <div class="card-body">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
      <div>
        <h5 class="card-title mb-1">Online Competition Admin</h5>
        <div class="text-muted small">Approve registered students and provide login credentials, category, practice kit, and competition schedule.</div>
      </div>
      <div class="d-flex gap-2">
        <span class="badge text-bg-warning">Pending: <?php echo $pendingCompetitionCount; ?></span>
        <span class="badge text-bg-success">Approved: <?php echo $approvedCompetitionCount; ?></span>
      </div>
    </div>

    <?php if ($competitionMessage !== ''): ?>
      <div class="alert alert-info"><?php echo htmlspecialchars($competitionMessage); ?></div>
    <?php endif; ?>

    <ul class="nav nav-pills gap-2 mb-3" id="competitionTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="registrations-tab" data-bs-toggle="tab" data-bs-target="#registrations-pane" type="button" role="tab">Registrations</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-pane" type="button" role="tab">Categories</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="provide-tab" data-bs-toggle="tab" data-bs-target="#provide-pane" type="button" role="tab">Provide Details</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="schedule-new-tab" data-bs-toggle="tab" data-bs-target="#schedule-new-pane" type="button" role="tab">Schedule Competition</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="kits-tab" data-bs-toggle="tab" data-bs-target="#kits-pane" type="button" role="tab">Practice Kit Access</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule-pane" type="button" role="tab">Competition Schedule</button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="registrations-pane" role="tabpanel" tabindex="0">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Student</th>
                <th>Phone</th>
                <th>School</th>
                <th>Category</th>
                <th>Status</th>
                <th>Registered</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($competitionRegistrations)): ?>
                <tr><td colspan="6" class="text-muted">No online competition registrations yet.</td></tr>
              <?php else: ?>
                <?php foreach ($competitionRegistrations as $registration): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($registration['name'] ?? ''); ?></div>
                      <div class="text-muted small"><?php echo htmlspecialchars($registration['email'] ?? ''); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($registration['mobile'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($registration['school'] ?? '-'); ?></td>
                    <td>
                      <div><?php echo htmlspecialchars($registration['maats_category'] ?? '-'); ?></div>
                      <div class="text-muted small"><?php echo htmlspecialchars($registration['maats_subcategory'] ?? ''); ?></div>
                    </td>
                    <td><span class="badge <?php echo ($registration['status'] ?? '') === 'approved' ? 'text-bg-success' : 'text-bg-warning'; ?>"><?php echo htmlspecialchars(ucfirst($registration['status'] ?? 'pending')); ?></span></td>
                    <td><?php echo htmlspecialchars(admin_dashboard_format_datetime($registration['created_at'] ?? null)); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="categories-pane" role="tabpanel" tabindex="0">
        <div class="alert alert-light border mb-3">
          <div class="fw-semibold">Practice Papers Category Structure</div>
          <div class="small text-muted">These categories are available for Online Competition practice papers and scheduling.</div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Category</th>
                <th>Subcategory</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($competitionCategoryRows)): ?>
                <tr><td colspan="2" class="text-muted">No categories found.</td></tr>
              <?php else: ?>
                <?php foreach ($competitionCategoryRows as $row): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['subcategory_names'] ?? '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="provide-pane" role="tabpanel" tabindex="0">
        <?php if (empty($competitionRegistrations)): ?>
          <div class="text-muted">No student registration is available for assigning details.</div>
        <?php else: ?>
          <div class="accordion" id="competitionProvideAccordion">
            <?php foreach ($competitionRegistrations as $index => $registration): ?>
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#provide-<?php echo htmlspecialchars($registration['id']); ?>">
                    <?php echo htmlspecialchars($registration['name'] ?? 'Student'); ?> - <?php echo htmlspecialchars($registration['email'] ?? ''); ?>
                  </button>
                </h2>
                <div id="provide-<?php echo htmlspecialchars($registration['id']); ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#competitionProvideAccordion">
                  <div class="accordion-body">
                    <form method="post" class="row g-3">
                      <input type="hidden" name="action" value="provide_competition_details" />
                      <input type="hidden" name="competition_user_id" value="<?php echo htmlspecialchars($registration['id']); ?>" />
                      <div class="col-md-4">
                        <label class="form-label">Login Email</label>
                        <input class="form-control" value="<?php echo htmlspecialchars($registration['email'] ?? ''); ?>" readonly />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Login Password</label>
                        <input class="form-control" name="login_password" placeholder="Set password for student" required />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Practice Kit</label>
                        <input class="form-control" name="kit_title" value="<?php echo htmlspecialchars($registration['kit_title'] ?? '90 Days Practice Kit'); ?>" required />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Simple Abacus Category</label>
                        <input class="form-control" name="category" value="<?php echo htmlspecialchars($registration['maats_category'] ?? ''); ?>" placeholder="Category B" required />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Simple Abacus Subcategory</label>
                        <input class="form-control" name="subcategory" value="<?php echo htmlspecialchars($registration['maats_subcategory'] ?? ''); ?>" placeholder="Junior (Age 8-9)" required />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Competition Title</label>
                        <input class="form-control" name="competition_title" value="<?php echo htmlspecialchars($registration['competition_title'] ?? 'Online Competition'); ?>" required />
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Competition Date</label>
                        <input type="date" class="form-control" name="competition_date" value="<?php echo htmlspecialchars($registration['competition_date'] ?? ''); ?>" required />
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Start Time</label>
                        <input type="time" class="form-control" name="start_time" value="<?php echo htmlspecialchars(substr((string) ($registration['start_time'] ?? ''), 0, 5)); ?>" required />
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">End Time</label>
                        <input type="time" class="form-control" name="end_time" value="<?php echo htmlspecialchars(substr((string) ($registration['end_time'] ?? ''), 0, 5)); ?>" required />
                      </div>
                      <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Save</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="tab-pane fade" id="schedule-new-pane" role="tabpanel" tabindex="0">
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="schedule_online_competition" />
          <div class="col-lg-5">
            <label class="form-label">Competition Title</label>
            <input class="form-control" name="schedule_title" value="World Champion - June 2026" required />
          </div>
          <div class="col-lg-3">
            <label class="form-label">Simple Abacus Category</label>
            <input class="form-control" name="schedule_category" value="Category B" required />
          </div>
          <div class="col-lg-4">
            <label class="form-label">Simple Abacus Subcategory</label>
            <input class="form-control" name="schedule_subcategory" value="Junior (Age 8-9)" required />
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="schedule_description" rows="2">Short competition details for students</textarea>
          </div>
          <div class="col-md-3">
            <label class="form-label">Competition Date</label>
            <input type="date" class="form-control" name="schedule_date" value="<?php echo date('Y-m-d'); ?>" required />
          </div>
          <div class="col-md-2">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" name="schedule_start_time" value="10:00" required />
          </div>
          <div class="col-md-2">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" name="schedule_end_time" value="10:03" required />
          </div>
          <div class="col-md-2">
            <label class="form-label">Duration</label>
            <input type="number" class="form-control" name="schedule_duration" value="3" min="1" />
          </div>
          <div class="col-md-2">
            <label class="form-label">Questions</label>
            <input type="number" class="form-control" name="schedule_questions" value="60" min="0" />
          </div>
          <div class="col-md-1">
            <label class="form-label">Fee</label>
            <input type="number" class="form-control" name="schedule_fee" value="0" min="0" />
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit">Add Online Competition</button>
          </div>
        </form>

        <hr class="my-4" />
        <h6 class="mb-3">Scheduled Competitions</h6>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Competition</th><th>Category</th><th>Date</th><th>Time</th><th>Status</th><th>Questions</th></tr></thead>
            <tbody>
              <?php foreach ($scheduledCompetitions as $competition): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($competition['title'] ?? ''); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($competition['description'] ?? ''); ?></div>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($competition['category_name'] ?? '-'); ?>
                    <div class="text-muted small"><?php echo htmlspecialchars($competition['subcategory_name'] ?? ''); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($competition['competition_date'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars(substr((string) ($competition['start_time'] ?? '-'), 0, 5)); ?> - <?php echo htmlspecialchars(substr((string) ($competition['end_time'] ?? '-'), 0, 5)); ?></td>
                  <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars(ucfirst($competition['status'] ?? 'scheduled')); ?></span></td>
                  <td><?php echo htmlspecialchars((string) ($competition['total_questions'] ?? 0)); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($scheduledCompetitions)): ?>
                <tr><td colspan="6" class="text-muted">No competitions scheduled yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="kits-pane" role="tabpanel" tabindex="0">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Student</th><th>Practice Kit</th><th>Start Date</th><th>Expiry Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($competitionRegistrations as $registration): ?>
                <tr>
                  <td><?php echo htmlspecialchars($registration['name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($registration['kit_title'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars(admin_dashboard_format_datetime($registration['kit_start_date'] ?? null)); ?></td>
                  <td><?php echo htmlspecialchars(admin_dashboard_format_datetime($registration['kit_expiry_date'] ?? null)); ?></td>
                  <td><?php echo htmlspecialchars($registration['kit_status'] ?? 'Not assigned'); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($competitionRegistrations)): ?>
                <tr><td colspan="5" class="text-muted">No practice kit access assigned yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="schedule-pane" role="tabpanel" tabindex="0">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Student</th><th>Competition</th><th>Date</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($competitionRegistrations as $registration): ?>
                <tr>
                  <td><?php echo htmlspecialchars($registration['name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($registration['competition_title'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars($registration['competition_date'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars(substr((string) ($registration['start_time'] ?? '-'), 0, 5)); ?></td>
                  <td><?php echo htmlspecialchars(substr((string) ($registration['end_time'] ?? '-'), 0, 5)); ?></td>
                  <td><?php echo htmlspecialchars(ucfirst($registration['status'] ?? 'pending')); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($competitionRegistrations)): ?>
                <tr><td colspan="6" class="text-muted">No competition schedule assigned yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Overview Chart</h5>
        <canvas id="overviewChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
  const ctx = document.getElementById('overviewChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Students', 'Paid Fees', 'Unpaid Fees', 'Demos', 'Teachers', 'Pending Tutors'],
        datasets: [{
          label: 'Counts',
          data: [<?php echo $totalStudents; ?>, <?php echo $totalSubscriptions; ?>, <?php echo $totalUnpaidSubscriptions; ?>, <?php echo $totalDemos; ?>, <?php echo $totalTeachers; ?>, <?php echo $pendingInstructors; ?>],
          backgroundColor: ['#4b1e83', '#f97316', '#facc15', '#0ea5e9', '#22c55e', '#ef4444'],
          borderRadius: 8
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
