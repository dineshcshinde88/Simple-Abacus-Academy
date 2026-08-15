<?php
$pageTitle = 'Students';
$activeMenu = 'students';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

function admin_students_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_students_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_students_env_value(string $path, string $key): string
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

function admin_students_backend_pdo(): ?PDO
{
    $envPaths = [__DIR__ . '/../backend/.env', __DIR__ . '/../.env'];
    $envValue = static function (string $key) use ($envPaths): string {
        foreach ($envPaths as $path) {
            $value = admin_students_env_value($path, $key);
            if ($value !== '') return $value;
        }
        return '';
    };
    $host = '';
    $port = '3306';
    $db = '';
    $user = '';
    $pass = '';
    $databaseUrl = $envValue('DATABASE_URL');
    $parts = $databaseUrl !== '' ? parse_url($databaseUrl) : false;
    if (is_array($parts)) {
        $host = (string) ($parts['host'] ?? 'localhost');
        $port = (string) ($parts['port'] ?? '3306');
        $db = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';
        $user = isset($parts['user']) ? urldecode((string) $parts['user']) : '';
        $pass = isset($parts['pass']) ? urldecode((string) $parts['pass']) : '';
    }
    if ($db === '' || $user === '') {
        $host = $envValue('DB_HOST') ?: 'localhost';
        $port = $envValue('DB_PORT') ?: '3306';
        $db = $envValue('DB_DATABASE');
        $user = $envValue('DB_USERNAME');
        $pass = $envValue('DB_PASSWORD');
    }

    if ($db === '' || $user === '') {
        return null;
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

function admin_students_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!admin_students_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function admin_students_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function admin_students_table_type(PDO $pdo, string $table): string
{
    $stmt = $pdo->prepare(
        'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
    );
    $stmt->execute([$table]);
    return (string) $stmt->fetchColumn();
}

function admin_students_backend_storage(PDO $pdo): array
{
    if (
        admin_students_table_exists($pdo, 'Student')
        && admin_students_column_exists($pdo, 'Student', 'userId')
    ) {
        return [
            'table' => 'Student',
            'user_id' => 'userId',
            'tutor_id' => 'tutorId',
            'phone_country' => 'phoneCountry',
            'mother_tongue' => 'motherTongue',
            'whatsapp_number' => 'whatsappNumber',
            'level_id' => 'levelId',
            'subscription_status' => 'subscriptionStatus',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt',
        ];
    }

    if (
        admin_students_table_exists($pdo, 'students')
        && admin_students_column_exists($pdo, 'students', 'user_id')
    ) {
        return [
            'table' => 'students',
            'user_id' => 'user_id',
            'tutor_id' => 'tutor_id',
            'phone_country' => 'phone_country',
            'mother_tongue' => 'mother_tongue',
            'whatsapp_number' => 'whatsapp_number',
            'level_id' => 'level_id',
            'subscription_status' => 'subscription_status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    if (
        admin_students_table_exists($pdo, 'student')
        && admin_students_column_exists($pdo, 'student', 'userId')
    ) {
        return [
            'table' => 'student',
            'user_id' => 'userId',
            'tutor_id' => 'tutorId',
            'phone_country' => 'phoneCountry',
            'mother_tongue' => 'motherTongue',
            'whatsapp_number' => 'whatsappNumber',
            'level_id' => 'levelId',
            'subscription_status' => 'subscriptionStatus',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt',
        ];
    }

    if (
        admin_students_table_exists($pdo, 'students')
        && admin_students_column_exists($pdo, 'students', 'userId')
    ) {
        return [
            'table' => 'students',
            'user_id' => 'userId',
            'tutor_id' => 'tutorId',
            'phone_country' => 'phoneCountry',
            'mother_tongue' => 'motherTongue',
            'whatsapp_number' => 'whatsappNumber',
            'level_id' => 'levelId',
            'subscription_status' => 'subscriptionStatus',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt',
        ];
    }

    return [];
}

function admin_students_level_table(PDO $pdo): string
{
    if (admin_students_table_exists($pdo, 'Level')) {
        return 'Level';
    }
    if (admin_students_table_exists($pdo, 'levels')) {
        return 'levels';
    }
    return '';
}

function admin_students_level_name_column(PDO $pdo, string $table): string
{
    foreach (['level_name', 'levelName', 'name'] as $column) {
        if (admin_students_column_exists($pdo, $table, $column)) {
            return $column;
        }
    }
    return 'id';
}

function admin_students_valid_level_id(PDO $pdo, ?string $levelId): ?string
{
    $levelId = trim((string) $levelId);
    if ($levelId === '') {
        return null;
    }

    $table = admin_students_level_table($pdo);
    if ($table === '') {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->execute([$levelId]);
    return $stmt->fetch() ? $levelId : null;
}

function admin_students_user_table(PDO $pdo): string
{
    if (admin_students_table_exists($pdo, 'User')) {
        return 'User';
    }
    if (admin_students_table_exists($pdo, 'users')) {
        return 'users';
    }
    return 'users';
}

function admin_students_user_updated_column(PDO $pdo, string $userTable): string
{
    if (admin_students_column_exists($pdo, $userTable, 'updatedAt')) {
        return 'updatedAt';
    }
    if (admin_students_column_exists($pdo, $userTable, 'updated_at')) {
        return 'updated_at';
    }
    return '';
}

function admin_students_ensure_backend_profile_columns(PDO $pdo, array $storage): void
{
    if (!$storage || admin_students_table_type($pdo, $storage['table']) !== 'BASE TABLE') {
        return;
    }

    admin_students_ensure_column($pdo, $storage['table'], 'course', "VARCHAR(120) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, $storage['table'], $storage['phone_country'], "VARCHAR(10) NOT NULL DEFAULT '+91'");
    admin_students_ensure_column($pdo, $storage['table'], 'phone', "VARCHAR(40) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, $storage['table'], 'gender', "VARCHAR(30) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, $storage['table'], $storage['mother_tongue'], "VARCHAR(120) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, $storage['table'], $storage['whatsapp_number'], "VARCHAR(40) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, $storage['table'], 'dob', 'DATE NULL');
}

if (admin_students_table_type($pdo, 'students') === 'BASE TABLE') {
    admin_students_ensure_column($pdo, 'students', 'phone_country', "VARCHAR(10) NOT NULL DEFAULT '+91'");
    admin_students_ensure_column($pdo, 'students', 'gender', "VARCHAR(20) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, 'students', 'mother_tongue', "VARCHAR(120) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, 'students', 'whatsapp_number', "VARCHAR(40) NOT NULL DEFAULT ''");
    admin_students_ensure_column($pdo, 'students', 'dob', 'DATE NULL');
    admin_students_ensure_column($pdo, 'students', 'password', "VARCHAR(255) NOT NULL DEFAULT ''");
}

$adminStudentsHasLegacyColumns = admin_students_table_exists($pdo, 'students')
    && admin_students_column_exists($pdo, 'students', 'name')
    && admin_students_column_exists($pdo, 'students', 'email')
    && admin_students_column_exists($pdo, 'students', 'phone')
    && admin_students_column_exists($pdo, 'students', 'course');

$backendPdo = admin_students_backend_pdo();
if (
    !$backendPdo
    && (admin_students_table_exists($pdo, 'users') || admin_students_table_exists($pdo, 'User'))
    && (admin_students_table_exists($pdo, 'students') || admin_students_table_exists($pdo, 'Student'))
) {
    $backendPdo = $pdo;
}
$backendStorage = $backendPdo ? admin_students_backend_storage($backendPdo) : [];
$backendUserTable = $backendPdo ? admin_students_user_table($backendPdo) : 'users';
$backendUserUpdatedColumn = $backendPdo ? admin_students_user_updated_column($backendPdo, $backendUserTable) : 'updated_at';
if ($backendPdo && $backendStorage) {
    admin_students_ensure_backend_profile_columns($backendPdo, $backendStorage);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneCountry = trim($_POST['phone_country'] ?? '+91');
    $phone = trim($_POST['phone'] ?? '');
    $courseTypes = $_POST['course_types'] ?? [];
    if (!is_array($courseTypes)) {
        $courseTypes = [];
    }
    $courseTypes = array_values(array_intersect(['Abacus', 'Vedic Maths'], $courseTypes));
    $course = implode(', ', $courseTypes);
    $gender = trim($_POST['gender'] ?? '');
    $motherTongue = trim($_POST['mother_tongue'] ?? '');
    $whatsappNumber = preg_replace('/\D+/', '', (string) ($_POST['whatsapp_number'] ?? ''));
    $dob = trim($_POST['dob'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $postedStatus = (string) ($_POST['status'] ?? 'active');
    $status = in_array($postedStatus, ['active', 'inactive'], true) ? $postedStatus : 'active';

    if (($action === 'add' || $action === 'edit') && !$adminStudentsHasLegacyColumns) {
        $errors[] = 'Manual student editing is unavailable because this database uses the website student schema.';
    } elseif ($action === 'add' || $action === 'edit') {
        if (
            $name === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $phone === ''
            || $course === ''
            || $gender === ''
            || $whatsappNumber === ''
            || $dob === ''
            || ($action === 'add' && trim($password) === '')
        ) {
            $errors[] = 'Please fill all required fields with valid values.';
        }
    }

    if (!$errors && $action === 'add') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO students (name, email, phone_country, phone, course, gender, whatsapp_number, dob, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phoneCountry, $phone, $course, $gender, $whatsappNumber, $dob, $passwordHash, $status]);
        $success = 'Student added successfully.';
    }

    if (!$errors && $action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        if (trim($password) !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE students SET name = ?, email = ?, phone_country = ?, phone = ?, course = ?, gender = ?, whatsapp_number = ?, dob = ?, password = ?, status = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phoneCountry, $phone, $course, $gender, $whatsappNumber, $dob, $passwordHash, $status, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE students SET name = ?, email = ?, phone_country = ?, phone = ?, course = ?, gender = ?, whatsapp_number = ?, dob = ?, status = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phoneCountry, $phone, $course, $gender, $whatsappNumber, $dob, $status, $id]);
        }
        $success = 'Student updated successfully.';
    }

    if (!$errors && $action === 'add_website_student') {
        if (!$backendPdo || !$backendStorage) {
            $errors[] = 'Website student management is unavailable because the backend database is not connected.';
        } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || trim($password) === '') {
            $errors[] = 'Please enter student name, valid email, mobile number, and password.';
        } else {
            $existingUser = $backendPdo->prepare("SELECT id FROM {$backendUserTable} WHERE email = ? LIMIT 1");
            $existingUser->execute([strtolower($email)]);
            if ($existingUser->fetch()) {
                $errors[] = 'A website user already exists with this email.';
            } else {
                $now = date('Y-m-d H:i:s');
                $userId = admin_students_uuid();
                $studentId = admin_students_uuid();
                $studentTable = $backendStorage['table'];
                $studentTableType = admin_students_table_type($backendPdo, $studentTable);
                $studentColumns = ['id', $backendStorage['user_id']];
                $studentValues = [$studentId, $userId];
                $validatedLevelId = admin_students_valid_level_id($backendPdo, $_POST['level_id'] ?? null);
                $studentColumnMap = [
                    'course' => trim($_POST['website_course'] ?? '') !== '' ? trim($_POST['website_course']) : $course,
                    $backendStorage['phone_country'] => $phoneCountry !== '' ? $phoneCountry : '+91',
                    'phone' => $phone,
                    'gender' => $gender,
                    $backendStorage['mother_tongue'] => $motherTongue,
                    $backendStorage['whatsapp_number'] => $whatsappNumber,
                    'dob' => $dob !== '' ? $dob : null,
                    $backendStorage['subscription_status'] => in_array(($_POST['website_status'] ?? 'expired'), ['active', 'expired'], true) ? $_POST['website_status'] : 'expired',
                ];
                if ($backendStorage['level_id'] !== '' && admin_students_column_exists($backendPdo, $studentTable, $backendStorage['level_id'])) {
                    $studentColumnMap[$backendStorage['level_id']] = $validatedLevelId;
                }
                if ($studentTableType === 'BASE TABLE' && $backendStorage['created_at'] !== '' && admin_students_column_exists($backendPdo, $studentTable, $backendStorage['created_at'])) {
                    $studentColumnMap[$backendStorage['created_at']] = $now;
                }
                if ($studentTableType === 'BASE TABLE' && $backendStorage['updated_at'] !== '' && admin_students_column_exists($backendPdo, $studentTable, $backendStorage['updated_at'])) {
                    $studentColumnMap[$backendStorage['updated_at']] = $now;
                }

                foreach ($studentColumnMap as $column => $value) {
                    if ($column !== '' && admin_students_column_exists($backendPdo, $studentTable, $column)) {
                        $studentColumns[] = $column;
                        $studentValues[] = $value;
                    }
                }

                try {
                    $backendPdo->beginTransaction();
                    $userColumns = ['id', 'name', 'email', 'password', 'role'];
                    $userValues = [$userId, $name, strtolower($email), password_hash($password, PASSWORD_BCRYPT), 'student'];
                    if (admin_students_column_exists($backendPdo, $backendUserTable, 'createdAt')) {
                        $userColumns[] = 'createdAt';
                        $userValues[] = $now;
                    } elseif (admin_students_column_exists($backendPdo, $backendUserTable, 'created_at')) {
                        $userColumns[] = 'created_at';
                        $userValues[] = $now;
                    }
                    if ($backendUserUpdatedColumn !== '') {
                        $userColumns[] = $backendUserUpdatedColumn;
                        $userValues[] = $now;
                    }
                    $userPlaceholders = implode(', ', array_fill(0, count($userColumns), '?'));
                    $backendPdo
                        ->prepare("INSERT INTO {$backendUserTable} (" . implode(', ', $userColumns) . ") VALUES ({$userPlaceholders})")
                        ->execute($userValues);

                    $placeholders = implode(', ', array_fill(0, count($studentColumns), '?'));
                    $backendPdo
                        ->prepare("INSERT INTO {$studentTable} (" . implode(', ', $studentColumns) . ") VALUES ({$placeholders})")
                        ->execute($studentValues);

                    $backendPdo->commit();
                    $success = 'Website student added successfully.';
                } catch (Throwable $e) {
                    if ($backendPdo->inTransaction()) {
                        $backendPdo->rollBack();
                    }
                    $errors[] = 'Website student could not be added: ' . $e->getMessage();
                }
            }
        }
    }

    if (!$errors && $action === 'edit_website_student') {
        if (!$backendPdo || !$backendStorage) {
            $errors[] = 'Website student management is unavailable because the backend database is not connected.';
        } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid student name and email.';
        } else {
            $studentId = (string) ($_POST['website_student_id'] ?? '');
            $studentTable = $backendStorage['table'];
            $studentUserColumn = $backendStorage['user_id'];
            $studentRow = $backendPdo
                ->prepare("SELECT id, {$studentUserColumn} AS user_id FROM {$studentTable} WHERE id = ? LIMIT 1");
            $studentRow->execute([$studentId]);
            $studentRecord = $studentRow->fetch();

            if (!$studentRecord) {
                $errors[] = 'Website student not found.';
            } else {
                $now = date('Y-m-d H:i:s');
                $passwordSql = '';
                $userParams = [
                    'name' => $name,
                    'email' => strtolower($email),
                    'id' => $studentRecord['user_id'],
                ];
                if ($backendUserUpdatedColumn !== '') {
                    $userParams['updated_at'] = $now;
                }
                if (trim($password) !== '') {
                    $passwordSql = ', password = :password';
                    $userParams['password'] = password_hash($password, PASSWORD_BCRYPT);
                }
                $userUpdatedSql = $backendUserUpdatedColumn !== '' ? ", {$backendUserUpdatedColumn} = :updated_at" : '';

                $studentUpdates = [];
                $studentParams = ['id' => $studentId];
                $studentTable = $backendStorage['table'];
                $validatedLevelId = admin_students_valid_level_id($backendPdo, $_POST['level_id'] ?? null);
                $fieldMap = [
                    'course' => trim($_POST['website_course'] ?? ''),
                    $backendStorage['phone_country'] => $phoneCountry !== '' ? $phoneCountry : '+91',
                    'phone' => $phone,
                    'gender' => $gender,
                    $backendStorage['mother_tongue'] => $motherTongue,
                    $backendStorage['whatsapp_number'] => $whatsappNumber,
                    'dob' => $dob !== '' ? $dob : null,
                    $backendStorage['subscription_status'] => in_array(($_POST['website_status'] ?? 'expired'), ['active', 'expired'], true) ? $_POST['website_status'] : 'expired',
                ];
                if ($validatedLevelId !== null || trim((string) ($_POST['level_id'] ?? '')) === '') {
                    $fieldMap[$backendStorage['level_id']] = $validatedLevelId;
                }
                foreach ($fieldMap as $column => $value) {
                    if ($column !== '' && admin_students_column_exists($backendPdo, $studentTable, $column)) {
                        $param = 'student_' . preg_replace('/[^a-z0-9_]/i', '_', $column);
                        $studentUpdates[] = "{$column} = :{$param}";
                        $studentParams[$param] = $value;
                    }
                }
                if (admin_students_column_exists($backendPdo, $studentTable, $backendStorage['updated_at'])) {
                    $studentUpdates[] = "{$backendStorage['updated_at']} = :student_updated_at";
                    $studentParams['student_updated_at'] = $now;
                }

                try {
                    $backendPdo->beginTransaction();
                    $backendPdo
                        ->prepare("UPDATE {$backendUserTable} SET name = :name, email = :email{$userUpdatedSql}{$passwordSql} WHERE id = :id")
                        ->execute($userParams);
                    if ($studentUpdates) {
                        $backendPdo
                            ->prepare("UPDATE {$studentTable} SET " . implode(', ', $studentUpdates) . ' WHERE id = :id')
                            ->execute($studentParams);
                    }
                    $backendPdo->commit();
                    $success = 'Website student updated successfully.';
                } catch (Throwable $e) {
                    if ($backendPdo->inTransaction()) {
                        $backendPdo->rollBack();
                    }
                    $errors[] = 'Website student could not be updated: ' . $e->getMessage();
                }
            }
        }
    }

    if (!$errors && $action === 'delete_website_student') {
        if (!$backendPdo || !$backendStorage) {
            $errors[] = 'Website student management is unavailable because the backend database is not connected.';
        } else {
            $studentId = (string) ($_POST['website_student_id'] ?? '');
            $studentTable = $backendStorage['table'];
            $studentUserColumn = $backendStorage['user_id'];
            $studentRow = $backendPdo->prepare("SELECT id, {$studentUserColumn} AS user_id FROM {$studentTable} WHERE id = ? LIMIT 1");
            $studentRow->execute([$studentId]);
            $studentRecord = $studentRow->fetch();

            if (!$studentRecord) {
                $errors[] = 'Website student not found.';
            } else {
                try {
                    $backendPdo->beginTransaction();
                    $backendPdo->prepare("DELETE FROM {$backendStorage['table']} WHERE id = ?")->execute([$studentId]);
                    $backendPdo->prepare("DELETE FROM {$backendUserTable} WHERE id = ?")->execute([$studentRecord['user_id']]);
                    $backendPdo->commit();
                    $success = 'Website student deleted successfully.';
                } catch (Throwable $e) {
                    if ($backendPdo->inTransaction()) {
                        $backendPdo->rollBack();
                    }
                    $errors[] = 'Website student could not be deleted: ' . $e->getMessage();
                }
            }
        }
    }
}

if ($adminStudentsHasLegacyColumns && isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $success = 'Student deleted successfully.';
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR course LIKE ? OR whatsapp_number LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$hasLegacyStudentColumns = $adminStudentsHasLegacyColumns;

$students = [];
$totalRows = 0;
$totalPages = 0;
if ($hasLegacyStudentColumns) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM students {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int) $countStmt->fetchColumn();
    $totalPages = (int) ceil($totalRows / $limit);

    $listStmt = $pdo->prepare("SELECT * FROM students {$whereSql} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
    $listStmt->execute($params);
    $students = $listStmt->fetchAll();
}

$websiteStudents = [];
$websiteEditStudent = null;
$websiteLevels = [];
if ($backendPdo && $backendStorage && admin_students_table_exists($backendPdo, $backendUserTable)) {
    $studentTable = $backendStorage['table'];
    $studentUserColumn = $backendStorage['user_id'];
    $studentLevelColumn = $backendStorage['level_id'];
    $studentStatusColumn = $backendStorage['subscription_status'];
    $studentCreatedColumn = $backendStorage['created_at'];
    $hasStudentCourse = admin_students_column_exists($backendPdo, $studentTable, 'course');
    $hasStudentPhoneCountry = admin_students_column_exists($backendPdo, $studentTable, $backendStorage['phone_country']);
    $hasStudentPhone = admin_students_column_exists($backendPdo, $studentTable, 'phone');
    $hasStudentGender = admin_students_column_exists($backendPdo, $studentTable, 'gender');
    $hasStudentMotherTongue = admin_students_column_exists($backendPdo, $studentTable, $backendStorage['mother_tongue']);
    $hasStudentWhatsapp = admin_students_column_exists($backendPdo, $studentTable, $backendStorage['whatsapp_number']);
    $hasStudentDob = admin_students_column_exists($backendPdo, $studentTable, 'dob');
    $hasStudentLevel = admin_students_column_exists($backendPdo, $studentTable, $studentLevelColumn);
    $hasStudentSubscriptionStatus = admin_students_column_exists($backendPdo, $studentTable, $studentStatusColumn);
    $hasStudentCreatedAt = admin_students_column_exists($backendPdo, $studentTable, $studentCreatedColumn);
    $subscriptionTable = admin_students_table_exists($backendPdo, 'student_subscriptions') ? 'student_subscriptions' : '';
    $hasSubscriptionLevel = $subscriptionTable !== ''
        && admin_students_column_exists($backendPdo, $subscriptionTable, 'student_id')
        && admin_students_column_exists($backendPdo, $subscriptionTable, 'level_id');
    $studentTutorColumn = $backendStorage['tutor_id'] ?? 'tutor_id';
    $tutorTable = admin_students_table_exists($backendPdo, 'Tutor') ? 'Tutor' : 'tutors';
    $tutorUserColumn = admin_students_column_exists($backendPdo, $tutorTable, 'userId') ? 'userId' : 'user_id';
    $hasStudentTutor = admin_students_column_exists($backendPdo, $studentTable, $studentTutorColumn)
        && admin_students_table_exists($backendPdo, $tutorTable);
    $levelTable = admin_students_level_table($backendPdo);
    $levelNameColumn = $levelTable !== '' ? admin_students_level_name_column($backendPdo, $levelTable) : '';
    $hasLevels = $hasStudentLevel && $levelTable !== '' && admin_students_column_exists($backendPdo, $levelTable, 'id');
    $levelJoin = $hasLevels ? "LEFT JOIN {$levelTable} l ON l.id = s.{$studentLevelColumn}" : '';
    $instructorJoin = $hasStudentTutor
        ? "LEFT JOIN {$tutorTable} t ON t.id = s.{$studentTutorColumn} LEFT JOIN {$backendUserTable} iu ON iu.id = t.{$tutorUserColumn}"
        : '';
    $instructorSelect = $hasStudentTutor ? "COALESCE(NULLIF(iu.name, ''), 'Not assigned')" : "'Not assigned'";
    $courseSelect = $hasLevels
        ? ($hasStudentCourse
            ? "CONCAT(COALESCE(NULLIF(s.course, ''), 'Course not assigned'), ' / ', COALESCE(l.{$levelNameColumn}, 'Level not assigned'))"
            : "COALESCE(l.{$levelNameColumn}, 'Not assigned')")
        : "'Not assigned'";
    if (!$hasLevels && $hasStudentCourse) {
        $courseSelect = "COALESCE(NULLIF(s.course, ''), 'Not assigned')";
    }
    $phoneSelect = $hasStudentPhone
        ? ($hasStudentPhoneCountry
            ? "CASE WHEN COALESCE(NULLIF(s.phone, ''), '') = '' THEN '-' ELSE CONCAT(COALESCE(NULLIF(s.{$backendStorage['phone_country']}, ''), '+91'), ' ', s.phone) END"
            : "COALESCE(NULLIF(s.phone, ''), '-')")
        : "'-'";
    $statusSelect = $hasStudentSubscriptionStatus ? "COALESCE(s.{$studentStatusColumn}, 'registered')" : "'registered'";
    $createdSelect = $hasStudentCreatedAt ? "s.{$studentCreatedColumn}" : 'u.created_at';

    if ($hasLevels) {
        $websiteLevels = $backendPdo
            ->query("SELECT id, {$levelNameColumn} AS level_name FROM {$levelTable} ORDER BY {$levelNameColumn} ASC")
            ->fetchAll();
    }

    if (isset($_GET['edit_website'])) {
        $websiteEditId = (string) $_GET['edit_website'];
        $editLevelSelect = $hasStudentLevel ? "s.{$studentLevelColumn}" : 'NULL';
        if ($hasSubscriptionLevel && $hasLevels) {
            $subscriptionOrder = admin_students_column_exists($backendPdo, $subscriptionTable, 'created_at')
                ? 'ss.created_at DESC'
                : 'ss.id DESC';
            $editLevelSelect = "COALESCE({$editLevelSelect}, (SELECT ss.level_id FROM {$subscriptionTable} ss INNER JOIN {$levelTable} sl ON sl.id = ss.level_id WHERE ss.student_id = s.id ORDER BY {$subscriptionOrder} LIMIT 1))";
        }
        $editSelect = [
            's.id',
            "s.{$studentUserColumn} AS user_id",
            'u.name',
            'u.email',
            ($hasStudentCourse ? 's.course' : "'' AS course"),
            ($hasStudentPhoneCountry ? "s.{$backendStorage['phone_country']} AS phone_country" : "'+91' AS phone_country"),
            ($hasStudentPhone ? 's.phone' : "'' AS phone"),
            ($hasStudentGender ? 's.gender' : "'' AS gender"),
            ($hasStudentMotherTongue ? "s.{$backendStorage['mother_tongue']} AS mother_tongue" : "'' AS mother_tongue"),
            ($hasStudentWhatsapp
                ? "COALESCE(NULLIF(s.{$backendStorage['whatsapp_number']}, ''), " . ($hasStudentMotherTongue ? "NULLIF(s.{$backendStorage['mother_tongue']}, '')" : "''") . ", '') AS whatsapp_number"
                : ($hasStudentMotherTongue ? "s.{$backendStorage['mother_tongue']} AS whatsapp_number" : "'' AS whatsapp_number")),
            ($hasStudentDob ? 's.dob' : 'NULL AS dob'),
            "{$editLevelSelect} AS level_id",
            ($hasStudentSubscriptionStatus ? "s.{$studentStatusColumn} AS status" : "'expired' AS status"),
        ];
        $editStmt = $backendPdo->prepare("SELECT " . implode(', ', $editSelect) . " FROM {$studentTable} s INNER JOIN {$backendUserTable} u ON u.id = s.{$studentUserColumn} WHERE s.id = ? LIMIT 1");
        $editStmt->execute([$websiteEditId]);
        $websiteEditStudent = $editStmt->fetch();
    }

    $websiteWhere = ["u.role = 'student'"];
    $websiteParams = [];
    if ($search !== '') {
        $searchFields = ['u.name', 'u.email'];
        if ($hasStudentPhone) $searchFields[] = 's.phone';
        if ($hasStudentCourse) $searchFields[] = 's.course';
        if ($hasLevels) $searchFields[] = "l.{$levelNameColumn}";
        $websiteWhere[] = '(' . implode(' OR ', array_map(static fn(string $field): string => "{$field} LIKE ?", $searchFields)) . ')';
        foreach ($searchFields as $_) $websiteParams[] = "%{$search}%";
    }
    if ($hasStudentSubscriptionStatus && $statusFilter === 'active') {
        $websiteWhere[] = "s.{$studentStatusColumn} = 'active'";
    } elseif ($hasStudentSubscriptionStatus && $statusFilter === 'inactive') {
        $websiteWhere[] = "s.{$studentStatusColumn} <> 'active'";
    }

    $websiteSql = "
        SELECT
          s.id,
          s.{$studentUserColumn} AS user_id,
          u.name,
          u.email,
          {$phoneSelect} AS phone,
          {$courseSelect} AS course,
          {$instructorSelect} AS instructor_name,
          {$statusSelect} AS status,
          {$createdSelect} AS created_at
        FROM {$studentTable} s
        INNER JOIN {$backendUserTable} u ON u.id = s.{$studentUserColumn}
        {$levelJoin}
        {$instructorJoin}
        WHERE " . implode(' AND ', $websiteWhere) . "
        ORDER BY {$createdSelect} DESC
        LIMIT 100
    ";
    try {
        $websiteStmt = $backendPdo->prepare($websiteSql);
        $websiteStmt->execute($websiteParams);
        $websiteStudents = $websiteStmt->fetchAll();
    } catch (Throwable $e) {
        $errors[] = 'Website students could not be loaded: ' . $e->getMessage();
    }
}

$editStudent = null;
if ($hasLegacyStudentColumns && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$editId]);
    $editStudent = $stmt->fetch();
}

$viewStudent = null;
if ($hasLegacyStudentColumns && isset($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$viewId]);
    $viewStudent = $stmt->fetch();
}
?>

<?php if ($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $err): ?>
      <div><?php echo htmlspecialchars($err); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="row g-4">
  <?php if ($hasLegacyStudentColumns && !($backendPdo && $backendStorage)): ?>
    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="card-title"><?php echo $editStudent ? 'Edit Student' : 'Add Student'; ?></h5>
          <form method="post">
            <input type="hidden" name="action" value="<?php echo $editStudent ? 'edit' : 'add'; ?>" />
            <?php if ($editStudent): ?>
              <input type="hidden" name="id" value="<?php echo (int) $editStudent['id']; ?>" />
            <?php endif; ?>
            <?php
              $selectedCourses = array_map('trim', explode(',', (string) ($editStudent['course'] ?? 'Abacus')));
              $selectedGender = (string) ($editStudent['gender'] ?? '');
            ?>
            <div class="mb-3 d-flex justify-content-center gap-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="course_types[]" value="Abacus" id="course-abacus" <?php echo in_array('Abacus', $selectedCourses, true) ? 'checked' : ''; ?> />
                <label class="form-check-label fw-semibold" for="course-abacus">Abacus</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="course_types[]" value="Vedic Maths" id="course-vedic" <?php echo in_array('Vedic Maths', $selectedCourses, true) ? 'checked' : ''; ?> />
                <label class="form-check-label fw-semibold" for="course-vedic">Vedic Maths</label>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editStudent['name'] ?? ''); ?>" placeholder="Full Name" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editStudent['email'] ?? ''); ?>" placeholder="Email" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Mobile Number</label>
              <div class="input-group">
                <select name="phone_country" class="form-select" style="max-width: 110px;">
                  <?php foreach (['+91' => 'IN +91', '+1' => 'US +1', '+44' => 'UK +44', '+971' => 'UAE +971'] as $code => $label): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo (($editStudent['phone_country'] ?? '+91') === $code) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editStudent['phone'] ?? ''); ?>" placeholder="Mobile Number" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select" required>
                <option value="">Select Gender</option>
                <option value="male" <?php echo $selectedGender === 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo $selectedGender === 'female' ? 'selected' : ''; ?>>Female</option>
                <option value="other" <?php echo $selectedGender === 'other' ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">WhatsApp Number</label>
              <input type="text" name="whatsapp_number" class="form-control" value="<?php echo htmlspecialchars($editStudent['whatsapp_number'] ?? ''); ?>" placeholder="WhatsApp Number" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Date Of Birth</label>
              <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($editStudent['dob'] ?? ''); ?>" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="<?php echo $editStudent ? 'Leave blank to keep current password' : 'Create Password'; ?>" <?php echo $editStudent ? '' : 'required'; ?> />
            </div>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars((string) ($editStudent['status'] ?? 'active')); ?>" />
            <button class="btn btn-primary w-100" type="submit"><?php echo $editStudent ? 'Update Student' : 'Add Student'; ?></button>
            <?php if ($editStudent): ?>
              <a href="students.php" class="btn btn-link w-100">Cancel Edit</a>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  <?php elseif (false): ?>
    <div class="col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="card-title">Add Website Student</h5>
          <form method="post">
            <input type="hidden" name="action" value="add_website_student" />
            <div class="mb-3 d-flex justify-content-center gap-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="course_types[]" value="Abacus" id="website-course-abacus" checked />
                <label class="form-check-label fw-semibold" for="website-course-abacus">Abacus</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="course_types[]" value="Vedic Maths" id="website-course-vedic" />
                <label class="form-check-label fw-semibold" for="website-course-vedic">Vedic Maths</label>
              </div>
            </div>
            <input type="hidden" name="website_course" value="" />
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="Full Name" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="Email" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Mobile Number</label>
              <div class="input-group">
                <select name="phone_country" class="form-select" style="max-width: 110px;">
                  <?php foreach (['+91' => 'IN +91', '+1' => 'US +1', '+44' => 'UK +44', '+971' => 'UAE +971'] as $code => $label): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" name="phone" class="form-control" placeholder="Mobile Number" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Level</label>
              <select name="level_id" class="form-select">
                <option value="">Not assigned</option>
                <?php foreach ($websiteLevels as $level): ?>
                  <option value="<?php echo htmlspecialchars((string) $level['id']); ?>"><?php echo htmlspecialchars($level['level_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="website_status" class="form-select">
                <option value="expired">Expired</option>
                <option value="active">Active</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">WhatsApp Number</label>
              <input type="text" name="whatsapp_number" class="form-control" placeholder="WhatsApp Number" />
            </div>
            <div class="mb-3">
              <label class="form-label">Date of Birth</label>
              <input type="date" name="dob" class="form-control" />
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="Create Password" required />
            </div>
            <button class="btn btn-primary w-100" type="submit">Add Student</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <div class="col-12">
    <?php if ($viewStudent): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="card-title">Student Details</h5>
          <div class="row">
            <div class="col-md-6"><strong>Full Name:</strong> <?php echo htmlspecialchars($viewStudent['name']); ?></div>
            <div class="col-md-6"><strong>Email:</strong> <?php echo htmlspecialchars($viewStudent['email']); ?></div>
            <div class="col-md-6 mt-2"><strong>Mobile Number:</strong> <?php echo htmlspecialchars(trim(($viewStudent['phone_country'] ?? '') . ' ' . ($viewStudent['phone'] ?? ''))); ?></div>
            <div class="col-md-6 mt-2"><strong>Course:</strong> <?php echo htmlspecialchars($viewStudent['course']); ?></div>
            <div class="col-md-6 mt-2"><strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst((string) ($viewStudent['gender'] ?? 'Not added'))); ?></div>
            <div class="col-md-6 mt-2"><strong>WhatsApp Number:</strong> <?php echo htmlspecialchars($viewStudent['whatsapp_number'] ?? $viewStudent['mother_tongue'] ?? 'Not added'); ?></div>
            <div class="col-md-6 mt-2"><strong>Date Of Birth:</strong> <?php echo htmlspecialchars($viewStudent['dob'] ?? 'Not added'); ?></div>
            <div class="col-md-6 mt-2"><strong>Status:</strong> <?php echo htmlspecialchars($viewStudent['status']); ?></div>
            <div class="col-md-6 mt-2"><strong>Created:</strong> <?php echo htmlspecialchars($viewStudent['created_at']); ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($websiteEditStudent): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
              <h5 class="card-title mb-1">Edit Website Student</h5>
              <div class="text-muted small">Update details for students registered from the website/app.</div>
            </div>
            <a href="students.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
          </div>
          <form method="post">
            <input type="hidden" name="action" value="edit_website_student" />
            <input type="hidden" name="website_student_id" value="<?php echo htmlspecialchars((string) $websiteEditStudent['id']); ?>" />
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($websiteEditStudent['name'] ?? ''); ?>" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($websiteEditStudent['email'] ?? ''); ?>" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Mobile Number</label>
                <div class="input-group">
                  <select name="phone_country" class="form-select" style="max-width: 110px;">
                    <?php foreach (['+91' => 'IN +91', '+1' => 'US +1', '+44' => 'UK +44', '+971' => 'UAE +971'] as $code => $label): ?>
                      <option value="<?php echo htmlspecialchars($code); ?>" <?php echo (($websiteEditStudent['phone_country'] ?? '+91') === $code) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($websiteEditStudent['phone'] ?? ''); ?>" />
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Course</label>
                <input type="text" name="website_course" class="form-control" value="<?php echo htmlspecialchars($websiteEditStudent['course'] ?? ''); ?>" placeholder="Abacus / Vedic Maths" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Level</label>
                <select name="level_id" class="form-select">
                  <option value="">Not assigned</option>
                  <?php foreach ($websiteLevels as $level): ?>
                    <option value="<?php echo htmlspecialchars((string) $level['id']); ?>" <?php echo ((string) ($websiteEditStudent['level_id'] ?? '') === (string) $level['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($level['level_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="website_status" class="form-select">
                  <?php foreach (['active' => 'Active', 'expired' => 'Expired'] as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo (($websiteEditStudent['status'] ?? 'expired') === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                  <option value="">Not added</option>
                  <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo (($websiteEditStudent['gender'] ?? '') === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">WhatsApp Number</label>
                <input type="text" name="whatsapp_number" class="form-control" inputmode="numeric" value="<?php echo htmlspecialchars($websiteEditStudent['whatsapp_number'] ?? ''); ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Date Of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($websiteEditStudent['dob'] ?? ''); ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password" />
              </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit">Update Website Student</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($backendPdo && $backendStorage): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div><h5 class="card-title mb-1">All Students</h5><div class="text-muted small">Website students and students registered under instructor login.</div></div>
            <div class="d-flex gap-2 d-print-none">
              <button type="button" class="btn btn-outline-success btn-sm" onclick="downloadTableCsv('admin-student-table','student-list.csv')">Download Excel</button>
              <button type="button" class="btn btn-outline-danger btn-sm" onclick="printAdminTable('admin-student-table','Student List')">Download PDF</button>
            </div>
          </div>
          <form class="d-flex flex-wrap gap-2 mb-3" method="get">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" style="max-width: 280px;" placeholder="Search by name, email, or level" />
            <select name="status" class="form-select" style="max-width: 180px;">
              <option value="">All Status</option>
              <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <button class="btn btn-outline-primary" type="submit">Filter</button>
          </form>
          <div class="table-responsive">
            <table class="table align-middle" id="admin-student-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Course / Level</th>
                  <th>Instructor</th>
                  <th>Status</th>
                  <th>Enrolled</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($websiteStudents as $student): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                    <td><?php echo htmlspecialchars($student['instructor_name'] ?? 'Not assigned'); ?></td>
                    <td>
                      <span class="badge bg-<?php echo ($student['status'] ?? '') === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo htmlspecialchars($student['status'] ?? 'expired'); ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars($student['created_at']); ?></td>
                    <td>
                      <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="students.php?edit_website=<?php echo urlencode((string) $student['id']); ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this website student? This also removes their login account.');">
                          <input type="hidden" name="action" value="delete_website_student" />
                          <input type="hidden" name="website_student_id" value="<?php echo htmlspecialchars((string) $student['id']); ?>" />
                          <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$websiteStudents): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4">No students found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!($backendPdo && $backendStorage)): ?>
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
          <form class="d-flex gap-2" method="get">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search students" />
            <select name="status" class="form-select">
              <option value="">All Status</option>
              <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <button class="btn btn-outline-primary" type="submit">Filter</button>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Course</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><?php echo htmlspecialchars($student['name'] ?? 'Not added'); ?></td>
                  <td><?php echo htmlspecialchars($student['email'] ?? 'Not added'); ?></td>
                  <td><?php echo htmlspecialchars(trim(($student['phone_country'] ?? '') . ' ' . ($student['phone'] ?? '')) ?: 'Not added'); ?></td>
                  <td><?php echo htmlspecialchars($student['course'] ?? 'Not assigned'); ?></td>
                  <td><?php echo htmlspecialchars(ucfirst((string) ($student['gender'] ?? 'Not added'))); ?></td>
                  <td>
                    <span class="badge bg-<?php echo ($student['status'] ?? '') === 'active' ? 'success' : 'secondary'; ?>">
                      <?php echo htmlspecialchars($student['status'] ?? 'inactive'); ?>
                    </span>
                  </td>
                  <td class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="students.php?view=<?php echo (int) $student['id']; ?>">View</a>
                    <a class="btn btn-sm btn-outline-primary" href="students.php?edit=<?php echo (int) $student['id']; ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-danger" href="students.php?delete=<?php echo (int) $student['id']; ?>" onclick="return confirm('Delete this student?');">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$students): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted">
                    No students found.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav>
            <ul class="pagination">
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
