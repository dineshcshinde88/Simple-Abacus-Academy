<?php
$pageTitle = 'Subscriptions';
$activeMenu = 'subscriptions';
require_once __DIR__ . '/includes/header.php';

$success = '';
$errors = [];

function admin_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_format_date(?string $value): string
{
    if (!$value) {
        return '-';
    }
    $ts = strtotime($value);
    return $ts ? date('d M Y', $ts) : $value;
}

function admin_duration_label(?int $days): string
{
    if ($days === 90) {
        return '3 Months';
    }
    if ($days === 365) {
        return '1 Year';
    }
    return $days ? $days . ' days' : '-';
}

function admin_payment_status_value(?string $status): string
{
    return $status === 'paid' ? 'paid' : 'unpaid';
}

function admin_payment_status_label(?string $status): string
{
    return admin_payment_status_value($status) === 'paid' ? 'Paid' : 'Unpaid';
}

function admin_payment_status_badge(?string $status): string
{
    return admin_payment_status_value($status) === 'paid' ? 'success' : 'warning';
}

function admin_level_status(?string $endDate): string
{
    if (!$endDate) {
        return '-';
    }
    $ts = strtotime($endDate);
    if (!$ts) {
        return '-';
    }
    return $ts >= time() ? 'Active' : 'Completed';
}

$hasNewSubscriptions = admin_table_exists($pdo, 'student_subscriptions')
    && admin_table_exists($pdo, 'users')
    && admin_table_exists($pdo, 'students');

if (!$hasNewSubscriptions && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $planName = trim($_POST['plan_name'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $paymentStatus = admin_payment_status_value($_POST['payment_status'] ?? 'unpaid');
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

$statusFilter = $_GET['status'] ?? '';
$subscriptions = [];
$students = [];

if ($hasNewSubscriptions) {
    $where = '';
    $params = [];
    if (in_array($statusFilter, ['paid', 'unpaid'], true)) {
        if ($statusFilter === 'unpaid') {
            $where = "WHERE ss.payment_status IN ('unpaid', 'pending')";
        } else {
            $where = 'WHERE ss.payment_status = ?';
            $params[] = $statusFilter;
        }
    }

    $sql = "
        SELECT
            ss.*,
            u.name AS student_name,
            u.email AS student_email,
            l.level_name,
            c.name AS course_name,
            c.slug AS course_slug,
            p.duration_days,
            pa.provider_payment_id,
            pa.provider_order_id,
            pa.status AS payment_attempt_status
        FROM student_subscriptions ss
        INNER JOIN students st ON st.id = ss.student_id
        INNER JOIN users u ON u.id = st.user_id
        LEFT JOIN levels l ON l.id = ss.level_id
        LEFT JOIN courses c ON c.id = l.course_id
        LEFT JOIN subscription_plans p ON p.id = ss.plan_id
        LEFT JOIN payment_attempts pa ON pa.id = ss.payment_attempt_id
        {$where}
        ORDER BY ss.created_at DESC
    ";
    $listStmt = $pdo->prepare($sql);
    $listStmt->execute($params);
    $subscriptions = $listStmt->fetchAll();
} else {
    if (isset($_GET['delete'])) {
        $id = (int) $_GET['delete'];
        $stmt = $pdo->prepare('DELETE FROM subscriptions WHERE id = ?');
        $stmt->execute([$id]);
        $success = 'Subscription deleted successfully.';
    }

    $where = '';
    $params = [];
    if (in_array($statusFilter, ['paid', 'unpaid'], true)) {
        if ($statusFilter === 'unpaid') {
            $where = "WHERE s.payment_status IN ('unpaid', 'pending')";
        } else {
            $where = 'WHERE s.payment_status = ?';
            $params[] = $statusFilter;
        }
    }

    $listStmt = $pdo->prepare("SELECT s.*, st.name AS student_name, st.email AS student_email FROM subscriptions s JOIN students st ON s.student_id = st.id {$where} ORDER BY s.id DESC");
    $listStmt->execute($params);
    $subscriptions = $listStmt->fetchAll();
    $students = $pdo->query('SELECT id, name FROM students ORDER BY name ASC')->fetchAll();
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

<?php if ($hasNewSubscriptions): ?>
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
          <h5 class="card-title mb-1">Student Level Subscriptions</h5>
          <div class="text-muted small">Razorpay worksheet subscription payments appear here after successful payment.</div>
        </div>
        <form method="get" class="d-flex gap-2">
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <?php foreach (['paid', 'unpaid'] as $status): ?>
              <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars(admin_payment_status_label($status)); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-outline-primary" type="submit">Filter</button>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Student</th>
              <th>Email</th>
              <th>Course</th>
              <th>Level</th>
              <th>Duration</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Start</th>
              <th>Expiry</th>
              <th>Status</th>
              <th>Razorpay ID</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subscriptions as $sub): ?>
              <tr>
                <td><?php echo htmlspecialchars($sub['student_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($sub['student_email'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($sub['course_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($sub['level_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars(admin_duration_label(isset($sub['duration_days']) ? (int) $sub['duration_days'] : null)); ?></td>
                <td><?php echo htmlspecialchars(($sub['currency'] ?? 'INR') . ' ' . number_format((float) ($sub['amount'] ?? 0), 2)); ?></td>
                <td>
                  <span class="badge bg-<?php echo admin_payment_status_badge($sub['payment_status'] ?? null); ?>">
                    <?php echo htmlspecialchars(admin_payment_status_label($sub['payment_status'] ?? null)); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars(admin_format_date($sub['start_date'] ?? null)); ?></td>
                <td><?php echo htmlspecialchars(admin_format_date($sub['expiry_date'] ?? null)); ?></td>
                <td><?php echo htmlspecialchars(admin_level_status($sub['expiry_date'] ?? null)); ?></td>
                <td class="small">
                  <?php echo htmlspecialchars($sub['razorpay_payment_id'] ?: ($sub['provider_payment_id'] ?? '-')); ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$subscriptions): ?>
              <tr><td colspan="10" class="text-center text-muted py-4">No subscriptions found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php else: ?>
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
                <option value="unpaid">Unpaid</option>
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
                <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
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
                  <th>Level Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($subscriptions as $sub): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($sub['plan_name']); ?></td>
                    <td><?php echo htmlspecialchars(number_format((float) $sub['amount'], 2)); ?></td>
                    <td><span class="badge bg-<?php echo admin_payment_status_badge($sub['payment_status'] ?? null); ?>"><?php echo htmlspecialchars(admin_payment_status_label($sub['payment_status'] ?? null)); ?></span></td>
                    <td><?php echo htmlspecialchars($sub['start_date']); ?> - <?php echo htmlspecialchars($sub['end_date']); ?></td>
                    <td><?php echo htmlspecialchars(admin_level_status($sub['end_date'] ?? null)); ?></td>
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
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
