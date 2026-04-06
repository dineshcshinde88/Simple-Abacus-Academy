<?php
$pageTitle = 'Subscriptions';
$activeMenu = 'subscriptions';
require_once __DIR__ . '/includes/header.php';

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $planName = trim($_POST['plan_name'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $paymentStatus = $_POST['payment_status'] ?? 'pending';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    if ($studentId <= 0 || $planName === '' || $startDate === '' || $endDate === '') {
        $errors[] = 'Please fill all required fields.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO subscriptions (student_id, plan_name, amount, payment_status, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$studentId, $planName, $amount, $paymentStatus, $startDate, $endDate]);
        $success = 'Subscription added successfully.';
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM subscriptions WHERE id = ?');
    $stmt->execute([$id]);
    $success = 'Subscription deleted successfully.';
}

$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if (in_array($statusFilter, ['paid', 'pending'], true)) {
    $where = 'WHERE s.payment_status = ?';
    $params[] = $statusFilter;
}

$listStmt = $pdo->prepare("SELECT s.*, st.name AS student_name FROM subscriptions s JOIN students st ON s.student_id = st.id {$where} ORDER BY s.id DESC");
$listStmt->execute($params);
$subscriptions = $listStmt->fetchAll();

$students = $pdo->query('SELECT id, name FROM students ORDER BY name ASC')->fetchAll();
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
        <h5 class="card-title">Add Subscription</h5>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Student</label>
            <select name="student_id" class="form-select" required>
              <option value="">Select student</option>
              <?php foreach ($students as $student): ?>
                <option value="<?php echo (int) $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Plan Name</label>
            <input type="text" name="plan_name" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" name="amount" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select">
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" required />
          </div>
          <button class="btn btn-primary w-100" type="submit">Add Subscription</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0">Subscriptions</h5>
          <form method="get" class="d-flex gap-2">
            <select name="status" class="form-select">
              <option value="">All Status</option>
              <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
              <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
            <button class="btn btn-outline-primary" type="submit">Filter</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Student</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Duration</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($subscriptions as $sub): ?>
                <tr>
                  <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                  <td><?php echo htmlspecialchars($sub['plan_name']); ?></td>
                  <td><?php echo htmlspecialchars(number_format((float) $sub['amount'], 2)); ?></td>
                  <td><span class="badge bg-<?php echo $sub['payment_status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($sub['payment_status']); ?></span></td>
                  <td><?php echo htmlspecialchars($sub['start_date']); ?> - <?php echo htmlspecialchars($sub['end_date']); ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-danger" href="subscriptions.php?delete=<?php echo (int) $sub['id']; ?>" onclick="return confirm('Delete this subscription?');">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$subscriptions): ?>
                <tr><td colspan="6" class="text-center text-muted">No subscriptions found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
