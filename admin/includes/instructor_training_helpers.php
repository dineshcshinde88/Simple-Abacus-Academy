<?php

function admin_training_env_value(string $path, string $key): string
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

function admin_training_database_name(PDO $pdo): string
{
    return (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
}

function admin_training_backend_pdo(PDO $adminPdo): PDO
{
    $databaseUrl = admin_training_env_value(__DIR__ . '/../../backend/.env', 'DATABASE_URL');
    if ($databaseUrl === '') {
        $databaseUrl = admin_training_env_value(__DIR__ . '/../../.env', 'DATABASE_URL');
    }

    $parts = $databaseUrl !== '' ? parse_url($databaseUrl) : false;
    if (!is_array($parts)) {
        return $adminPdo;
    }

    $host = (string) ($parts['host'] ?? 'localhost');
    $port = (string) ($parts['port'] ?? '3306');
    $db = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';
    $user = isset($parts['user']) ? urldecode((string) $parts['user']) : '';
    $pass = isset($parts['pass']) ? urldecode((string) $parts['pass']) : '';

    if ($db === '' || $user === '' || $db === admin_training_database_name($adminPdo)) {
        return $adminPdo;
    }

    return new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function admin_training_table_has_column(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_training_ensure_instructor_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS instructors (
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
            role VARCHAR(30) NOT NULL DEFAULT 'instructor',
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            reset_token VARCHAR(64) NULL,
            reset_expiry DATETIME NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    foreach ([
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
    ] as $column => $definition) {
        if (!admin_training_table_has_column($pdo, 'instructors', $column)) {
            $pdo->exec("ALTER TABLE instructors ADD COLUMN {$column} {$definition}");
        }
    }

    $pdo->exec("UPDATE instructors SET role = 'instructor' WHERE role IS NULL OR role = ''");
    $pdo->exec("UPDATE instructors SET status = CASE WHEN is_verified = 1 THEN 'approved' ELSE 'pending' END WHERE status IS NULL OR status = ''");
    $pdo->exec("UPDATE instructors SET status = 'approved' WHERE is_verified = 1 AND status = 'pending'");
}
