<?php
$pageTitle = 'Students';
$activeMenu = 'students';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

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
