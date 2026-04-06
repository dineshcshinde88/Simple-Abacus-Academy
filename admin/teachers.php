<?php
$pageTitle = 'Teachers';
$activeMenu = 'teachers';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $joiningDate = $_POST['joining_date'] ?? '';
    $status = $_POST['status'] ?? 'active';

    if ($action === 'add' || $action === 'edit') {
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $expertise === '' || $joiningDate === '') {
            $errors[] = 'Please fill all required fields.';
        }
    }

    if (!$errors && $action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO teachers (name, email, phone, expertise, joining_date, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $expertise, $joiningDate, $status]);
        $success = 'Teacher added successfully.';
    }

    if (!$errors && $action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE teachers SET name = ?, email = ?, phone = ?, expertise = ?, joining_date = ?, status = ? WHERE id = ?');
        $stmt->execute([$name, $email, $phone, $expertise, $joiningDate, $status, $id]);
        $success = 'Teacher updated successfully.';
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM teachers WHERE id = ?');
    $stmt->execute([$id]);
    $success = 'Teacher deleted successfully.';
}

$teachers = $pdo->query('SELECT * FROM teachers ORDER BY joining_date DESC')->fetchAll();

$editTeacher = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([$editId]);
    $editTeacher = $stmt->fetch();
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
        <h5 class="card-title"><?php echo $editTeacher ? 'Edit Teacher' : 'Add Teacher'; ?></h5>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $editTeacher ? 'edit' : 'add'; ?>" />
          <?php if ($editTeacher): ?>
            <input type="hidden" name="id" value="<?php echo (int) $editTeacher['id']; ?>" />
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editTeacher['name'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editTeacher['email'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editTeacher['phone'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Expertise</label>
            <input type="text" name="expertise" class="form-control" value="<?php echo htmlspecialchars($editTeacher['expertise'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Joining Date</label>
            <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($editTeacher['joining_date'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="active" <?php echo (($editTeacher['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo (($editTeacher['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
          <button class="btn btn-primary w-100" type="submit"><?php echo $editTeacher ? 'Update Teacher' : 'Add Teacher'; ?></button>
          <?php if ($editTeacher): ?>
            <a href="teachers.php" class="btn btn-link w-100">Cancel Edit</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Teacher List</h5>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Expertise</th>
                <th>Joining Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($teachers as $teacher): ?>
                <tr>
                  <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                  <td>
                    <div><?php echo htmlspecialchars($teacher['email']); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($teacher['expertise']); ?></td>
                  <td><?php echo htmlspecialchars($teacher['joining_date']); ?></td>
                  <td>
                    <span class="badge bg-<?php echo $teacher['status'] === 'active' ? 'success' : 'secondary'; ?>">
                      <?php echo htmlspecialchars($teacher['status']); ?>
                    </span>
                  </td>
                  <td class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="teachers.php?edit=<?php echo (int) $teacher['id']; ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-danger" href="teachers.php?delete=<?php echo (int) $teacher['id']; ?>" onclick="return confirm('Delete this teacher?');">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$teachers): ?>
                <tr><td colspan="6" class="text-center text-muted">No teachers found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
