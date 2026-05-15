<?php
$pageTitle = 'Instructors';
$activeMenu = 'instructors';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

function admin_instructors_env_value(string $path, string $key): string
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

function admin_instructors_database_name(PDO $pdo): string
{
    return (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
}

function admin_instructors_backend_pdo(PDO $adminPdo): PDO
{
    $databaseUrl = admin_instructors_env_value(__DIR__ . '/../backend/.env', 'DATABASE_URL');
    if ($databaseUrl === '') {
        $databaseUrl = admin_instructors_env_value(__DIR__ . '/../.env', 'DATABASE_URL');
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

    if ($db === '' || $user === '' || $db === admin_instructors_database_name($adminPdo)) {
        return $adminPdo;
    }

    return new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function admin_uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function admin_table_has_column(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensure_admin_instructor_schema(PDO $pdo): void
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
        'career_started' => 'VARCHAR(100) NULL',
        'students_trained' => 'VARCHAR(100) NULL',
        'address' => 'TEXT NULL',
        'profile_picture' => 'TEXT NULL',
        'reset_token' => 'VARCHAR(64) NULL',
        'reset_expiry' => 'DATETIME NULL',
    ] as $column => $definition) {
        if (!admin_table_has_column($pdo, 'instructors', $column)) {
            $pdo->exec("ALTER TABLE instructors ADD COLUMN {$column} {$definition}");
        }
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id CHAR(36) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tutors (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_tutors_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec("UPDATE instructors SET role = 'instructor' WHERE role IS NULL OR role = ''");
    $pdo->exec("UPDATE instructors SET status = CASE WHEN is_verified = 1 THEN 'approved' ELSE 'pending' END WHERE status IS NULL OR status = ''");
    $pdo->exec("UPDATE instructors SET status = 'approved' WHERE is_verified = 1 AND status = 'pending'");
}

function approve_instructor(PDO $pdo, string $id): void
{
    $stmt = $pdo->prepare('SELECT * FROM instructors WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $instructor = $stmt->fetch();
    if (!$instructor) {
        throw new RuntimeException('Instructor not found.');
    }
    if (trim((string) ($instructor['password'] ?? '')) === '') {
        throw new RuntimeException('This instructor application has no password. Ask the instructor to submit the tutor registration form again, then approve the new pending entry.');
    }

    $now = gmdate('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE instructors SET status = 'approved', is_verified = 1 WHERE id = ?")->execute([$id]);

        $userStmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $userStmt->execute([$instructor['email']]);
        $user = $userStmt->fetch();
        if ($user) {
            $userId = $user['id'];
            $pdo->prepare('UPDATE users SET name = ?, password = ?, role = ?, updated_at = ? WHERE id = ?')
                ->execute([$instructor['full_name'], $instructor['password'], 'tutor', $now, $userId]);
        } else {
            $userId = admin_uuid_v4();
            $pdo->prepare('INSERT INTO users (id, name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$userId, $instructor['full_name'], $instructor['email'], $instructor['password'], 'tutor', $now, $now]);
        }

        $tutorStmt = $pdo->prepare('SELECT id FROM tutors WHERE user_id = ? LIMIT 1');
        $tutorStmt->execute([$userId]);
        if (!$tutorStmt->fetch()) {
            $pdo->prepare('INSERT INTO tutors (id, user_id, created_at, updated_at) VALUES (?, ?, ?, ?)')
                ->execute([admin_uuid_v4(), $userId, $now, $now]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function admin_instructor_course_label(?string $courseType): string
{
    return match ((string) $courseType) {
        'abacus' => 'Abacus',
        'vedic_maths' => 'Vedic Maths',
        default => 'Not added',
    };
}

function admin_instructor_profile_picture_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
    if ($path !== '' && str_starts_with($path, '/uploads/')) {
        $fileName = basename($path);
        if (is_file(__DIR__ . '/../backend/uploads/' . $fileName)) {
            return '../backend/uploads/' . rawurlencode($fileName);
        }
    }

    return $url;
}

$instructorPdo = $pdo;
try {
    $instructorPdo = admin_instructors_backend_pdo($pdo);
    ensure_admin_instructor_schema($instructorPdo);
} catch (Throwable $e) {
    $errors[] = 'Instructor table setup failed: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (string) ($_POST['id'] ?? '');
    try {
        if ($action === 'approve') {
            approve_instructor($instructorPdo, $id);
            $success = 'Instructor approved successfully.';
        } elseif ($action === 'reject') {
            $instructorPdo->prepare("UPDATE instructors SET status = 'rejected', is_verified = 0 WHERE id = ?")->execute([$id]);
            $success = 'Instructor rejected.';
        } elseif ($action === 'delete') {
            $instructorPdo->prepare('DELETE FROM instructors WHERE id = ?')->execute([$id]);
            $success = 'Instructor deleted.';
        }
    } catch (Throwable $e) {
        $errors[] = 'Action failed: ' . $e->getMessage();
    }
}

$instructors = [];
try {
    $instructors = $instructorPdo->query("SELECT * FROM instructors ORDER BY FIELD(status, 'pending', 'approved', 'rejected'), created_at DESC")->fetchAll();
} catch (Throwable $e) {
    $errors[] = 'Instructor list could not be loaded: ' . $e->getMessage();
}

$groups = ['pending' => [], 'approved' => [], 'rejected' => []];
foreach ($instructors as $instructor) {
    $status = $instructor['status'] ?? 'pending';
    $groups[$status][] = $instructor;
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
  <?php foreach ($groups as $status => $rows): ?>
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0 text-capitalize"><?php echo htmlspecialchars($status); ?> Instructors</h5>
            <span class="badge bg-<?php echo $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning text-dark'); ?>">
              <?php echo count($rows); ?>
            </span>
          </div>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Contact</th>
                  <th>Details</th>
                  <th>Registered</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $instructor): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?php $profilePictureUrl = admin_instructor_profile_picture_url($instructor['profile_picture'] ?? ''); ?>
                        <?php if ($profilePictureUrl !== ''): ?>
                          <img
                            src="<?php echo htmlspecialchars($profilePictureUrl); ?>"
                            alt="<?php echo htmlspecialchars($instructor['full_name']); ?>"
                            class="rounded-circle"
                            style="width:44px;height:44px;object-fit:cover;"
                          />
                        <?php endif; ?>
                        <div>
                          <div><?php echo htmlspecialchars($instructor['full_name']); ?></div>
                          <div class="text-muted small"><?php echo htmlspecialchars(admin_instructor_course_label($instructor['course_type'] ?? null)); ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div><?php echo htmlspecialchars($instructor['email']); ?></div>
                      <div class="text-muted small"><?php echo htmlspecialchars($instructor['mobile']); ?></div>
                    </td>
                    <td>
                      <div class="small">Gender: <?php echo htmlspecialchars(ucfirst((string) ($instructor['gender'] ?? 'Not added'))); ?></div>
                      <div class="small">DOB: <?php echo htmlspecialchars((string) ($instructor['date_of_birth'] ?? 'Not added')); ?></div>
                      <div class="small">Qualification: <?php echo htmlspecialchars((string) ($instructor['qualification'] ?? 'Not added')); ?></div>
                      <div class="small">Career: <?php echo htmlspecialchars((string) ($instructor['career_started'] ?? 'Not added')); ?></div>
                      <div class="small">Students: <?php echo htmlspecialchars((string) ($instructor['students_trained'] ?? 'Not added')); ?></div>
                      <div class="text-muted small" style="max-width:260px;white-space:normal;"><?php echo htmlspecialchars((string) ($instructor['address'] ?? '')); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($instructor['created_at']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($instructor['status']); ?></span></td>
                    <td>
                      <div class="d-flex flex-wrap gap-2">
                        <?php if ($instructor['status'] !== 'approved'): ?>
                          <?php if (trim((string) ($instructor['password'] ?? '')) === ''): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Password missing</span>
                          <?php else: ?>
                            <form method="post">
                              <input type="hidden" name="id" value="<?php echo htmlspecialchars($instructor['id']); ?>" />
                              <input type="hidden" name="action" value="approve" />
                              <button class="btn btn-sm btn-success" type="submit">Approve</button>
                            </form>
                          <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($instructor['status'] !== 'rejected'): ?>
                          <form method="post">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($instructor['id']); ?>" />
                            <input type="hidden" name="action" value="reject" />
                            <button class="btn btn-sm btn-outline-warning" type="submit">Reject</button>
                          </form>
                        <?php endif; ?>
                        <form method="post" onsubmit="return confirm('Delete this instructor?');">
                          <input type="hidden" name="id" value="<?php echo htmlspecialchars($instructor['id']); ?>" />
                          <input type="hidden" name="action" value="delete" />
                          <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                  <tr><td colspan="6" class="text-center text-muted">No <?php echo htmlspecialchars($status); ?> instructors.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
