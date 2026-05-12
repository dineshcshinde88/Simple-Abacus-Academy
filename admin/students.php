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
    $databaseUrl = admin_students_env_value(__DIR__ . '/../backend/.env', 'DATABASE_URL');
    if ($databaseUrl === '') {
        $databaseUrl = admin_students_env_value(__DIR__ . '/../.env', 'DATABASE_URL');
    }
    $parts = $databaseUrl !== '' ? parse_url($databaseUrl) : false;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($action === 'add' || $action === 'edit') {
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $course === '') {
            $errors[] = 'Please fill all required fields with valid values.';
        }
    }

    if (!$errors && $action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO students (name, email, phone, course, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $course, $status]);
        $success = 'Student added successfully.';
    }

    if (!$errors && $action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE students SET name = ?, email = ?, phone = ?, course = ?, status = ? WHERE id = ?');
        $stmt->execute([$name, $email, $phone, $course, $status, $id]);
        $success = 'Student updated successfully.';
    }
}

if (isset($_GET['delete'])) {
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
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR course LIKE ?)';
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

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalRows / $limit);

$listStmt = $pdo->prepare("SELECT * FROM students {$whereSql} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
$listStmt->execute($params);
$students = $listStmt->fetchAll();

$websiteStudents = [];
$backendPdo = admin_students_backend_pdo();
if ($backendPdo
    && admin_students_table_exists($backendPdo, 'users')
    && admin_students_table_exists($backendPdo, 'students')
) {
    $websiteWhere = ["u.role = 'student'"];
    $websiteParams = [];
    if ($search !== '') {
        $websiteWhere[] = '(u.name LIKE ? OR u.email LIKE ? OR l.level_name LIKE ? OR c.name LIKE ?)';
        $websiteParams[] = "%{$search}%";
        $websiteParams[] = "%{$search}%";
        $websiteParams[] = "%{$search}%";
        $websiteParams[] = "%{$search}%";
    }
    if ($statusFilter === 'active') {
        $websiteWhere[] = "s.subscription_status = 'active'";
    } elseif ($statusFilter === 'inactive') {
        $websiteWhere[] = "s.subscription_status <> 'active'";
    }

    $websiteSql = "
        SELECT
          s.id,
          u.name,
          u.email,
          '-' AS phone,
          COALESCE(l.level_name, c.name, 'Not assigned') AS course,
          s.subscription_status AS status,
          s.created_at
        FROM students s
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN levels l ON l.id = s.level_id
        LEFT JOIN courses c ON c.id = l.course_id
        WHERE " . implode(' AND ', $websiteWhere) . "
        ORDER BY s.created_at DESC
        LIMIT 100
    ";
    $websiteStmt = $backendPdo->prepare($websiteSql);
    $websiteStmt->execute($websiteParams);
    $websiteStudents = $websiteStmt->fetchAll();
}

$editStudent = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$editId]);
    $editStudent = $stmt->fetch();
}

$viewStudent = null;
if (isset($_GET['view'])) {
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
  <div class="col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title"><?php echo $editStudent ? 'Edit Student' : 'Add Student'; ?></h5>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $editStudent ? 'edit' : 'add'; ?>" />
          <?php if ($editStudent): ?>
            <input type="hidden" name="id" value="<?php echo (int) $editStudent['id']; ?>" />
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editStudent['name'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editStudent['email'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editStudent['phone'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Course</label>
            <input type="text" name="course" class="form-control" value="<?php echo htmlspecialchars($editStudent['course'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="active" <?php echo (($editStudent['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo (($editStudent['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
          <button class="btn btn-primary w-100" type="submit"><?php echo $editStudent ? 'Update Student' : 'Add Student'; ?></button>
          <?php if ($editStudent): ?>
            <a href="students.php" class="btn btn-link w-100">Cancel Edit</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <?php if ($viewStudent): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="card-title">Student Details</h5>
          <div class="row">
            <div class="col-md-6"><strong>Name:</strong> <?php echo htmlspecialchars($viewStudent['name']); ?></div>
            <div class="col-md-6"><strong>Email:</strong> <?php echo htmlspecialchars($viewStudent['email']); ?></div>
            <div class="col-md-6 mt-2"><strong>Phone:</strong> <?php echo htmlspecialchars($viewStudent['phone']); ?></div>
            <div class="col-md-6 mt-2"><strong>Course:</strong> <?php echo htmlspecialchars($viewStudent['course']); ?></div>
            <div class="col-md-6 mt-2"><strong>Status:</strong> <?php echo htmlspecialchars($viewStudent['status']); ?></div>
            <div class="col-md-6 mt-2"><strong>Created:</strong> <?php echo htmlspecialchars($viewStudent['created_at']); ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($websiteStudents): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h5 class="card-title mb-1">Website Enrolled Students</h5>
          <div class="text-muted small mb-3">Students who registered from the website appear here automatically.</div>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Course / Level</th>
                  <th>Status</th>
                  <th>Enrolled</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($websiteStudents as $student): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                    <td>
                      <span class="badge bg-<?php echo ($student['status'] ?? '') === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo htmlspecialchars($student['status'] ?? 'expired'); ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars($student['created_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

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
                <th>Phone</th>
                <th>Course</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><?php echo htmlspecialchars($student['name']); ?></td>
                  <td><?php echo htmlspecialchars($student['email']); ?></td>
                  <td><?php echo htmlspecialchars($student['phone']); ?></td>
                  <td><?php echo htmlspecialchars($student['course']); ?></td>
                  <td>
                    <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>">
                      <?php echo htmlspecialchars($student['status']); ?>
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
                <tr><td colspan="6" class="text-center text-muted">No students found.</td></tr>
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
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
