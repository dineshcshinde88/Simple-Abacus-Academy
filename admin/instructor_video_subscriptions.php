<?php
$pageTitle = 'Instructor Video Subscriptions';
$activeMenu = 'instructor_video_subscriptions';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/instructor_training_helpers.php';

function ivs_uuid(): string {
  $data = random_bytes(16);
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function ivs_ensure_schema(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS instructor_video_subscriptions (
    id CHAR(36) PRIMARY KEY,
    instructor_id CHAR(36) NOT NULL,
    plan_name VARCHAR(80) NOT NULL DEFAULT '90 Days',
    duration_days INT NOT NULL DEFAULT 90,
    payment_method VARCHAR(40) NULL,
    payment_amount DECIMAL(10,2) NULL,
    payment_reference VARCHAR(255) NULL,
    payment_note TEXT NULL,
    start_date DATETIME NOT NULL,
    expiry_date DATETIME NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    activated_by_admin_id VARCHAR(64) NULL,
    activated_at DATETIME NULL,
    admin_note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_ivs_instructor_status (instructor_id, status),
    INDEX idx_ivs_expiry (expiry_date)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

$success = '';
$errors = [];
$videoPdo = $pdo;

try {
  admin_training_ensure_instructor_schema($pdo);
  ivs_ensure_schema($pdo);
  $videoPdo = admin_training_backend_pdo($pdo);
  admin_training_ensure_instructor_schema($videoPdo);
  ivs_ensure_schema($videoPdo);
  $videoPdo->exec("UPDATE instructor_video_subscriptions SET status = 'expired', updated_at = UTC_TIMESTAMP() WHERE status = 'active' AND expiry_date < UTC_TIMESTAMP()");
} catch (Throwable $e) {
  $errors[] = 'Instructor video subscription setup failed: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string) ($_POST['action'] ?? '');
  try {
    if ($action === 'activate' || $action === 'renew') {
      $instructorId = (string) ($_POST['instructor_id'] ?? '');
      $startDate = (string) ($_POST['start_date'] ?? date('Y-m-d'));
      $start = date('Y-m-d 00:00:00', strtotime($startDate));
      $expiry = date('Y-m-d 23:59:59', strtotime($start . ' +90 days'));
      if ($instructorId === '' || strtotime($startDate) === false) {
        throw new RuntimeException('Select instructor and activation start date.');
      }
      $videoPdo->prepare("UPDATE instructor_video_subscriptions SET status = 'expired', updated_at = UTC_TIMESTAMP() WHERE instructor_id = ? AND status = 'active'")->execute([$instructorId]);
      $stmt = $videoPdo->prepare("INSERT INTO instructor_video_subscriptions (
        id, instructor_id, plan_name, duration_days, payment_method, payment_amount, payment_reference,
        payment_note, start_date, expiry_date, status, activated_by_admin_id, activated_at, admin_note, created_at, updated_at
      ) VALUES (?, ?, '90 Days', 90, ?, ?, ?, ?, ?, ?, 'active', ?, UTC_TIMESTAMP(), ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
      $stmt->execute([
        ivs_uuid(),
        $instructorId,
        $_POST['payment_method'] ?? 'Cash',
        $_POST['payment_amount'] !== '' ? $_POST['payment_amount'] : null,
        trim((string) ($_POST['payment_reference'] ?? '')),
        trim((string) ($_POST['payment_note'] ?? '')),
        $start,
        $expiry,
        (string) ($_SESSION['admin_id'] ?? ''),
        trim((string) ($_POST['admin_note'] ?? '')),
      ]);
      $success = $action === 'renew' ? 'Subscription renewed.' : 'Subscription activated.';
    } elseif ($action === 'suspend') {
      $videoPdo->prepare("UPDATE instructor_video_subscriptions SET status = 'suspended', updated_at = UTC_TIMESTAMP() WHERE id = ?")->execute([(string) $_POST['id']]);
      $success = 'Subscription suspended.';
    } elseif ($action === 'reactivate') {
      $videoPdo->prepare("UPDATE instructor_video_subscriptions SET status = 'active', updated_at = UTC_TIMESTAMP() WHERE id = ? AND expiry_date >= UTC_TIMESTAMP()")->execute([(string) $_POST['id']]);
      $success = 'Subscription reactivated.';
    }
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }
}

$search = trim((string) ($_GET['q'] ?? ''));
$instructorSql = "SELECT id, full_name, email, mobile, status FROM instructors WHERE status = 'approved'";
$params = [];
if ($search !== '') {
  $instructorSql .= " AND (full_name LIKE ? OR email LIKE ? OR mobile LIKE ?)";
  $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}
$instructorSql .= " ORDER BY full_name LIMIT 50";
$stmt = $videoPdo->prepare($instructorSql);
$stmt->execute($params);
$instructors = $stmt->fetchAll();

$subscriptions = $videoPdo->query("SELECT s.*, i.full_name, i.email, i.mobile
  FROM instructor_video_subscriptions s
  LEFT JOIN instructors i ON i.id = s.instructor_id
  ORDER BY FIELD(s.status, 'active', 'pending', 'suspended', 'expired'), s.created_at DESC
  LIMIT 200")->fetchAll();
?>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Activate 90-Day Access</h5>
        <form class="mt-3" method="get">
          <label class="form-label">Search instructor</label>
          <div class="input-group">
            <input class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email or mobile" />
            <button class="btn btn-outline-primary">Search</button>
          </div>
        </form>
        <form class="mt-4" method="post">
          <input type="hidden" name="action" value="activate" />
          <label class="form-label">Instructor</label>
          <select class="form-select" name="instructor_id" required>
            <option value="">Select existing instructor</option>
            <?php foreach ($instructors as $instructor): ?>
              <option value="<?php echo htmlspecialchars($instructor['id']); ?>">
                <?php echo htmlspecialchars($instructor['full_name'] . ' - ' . $instructor['mobile']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="row g-3 mt-1">
            <div class="col-6">
              <label class="form-label">Plan</label>
              <input class="form-control" value="90 Days" readonly />
            </div>
            <div class="col-6">
              <label class="form-label">Amount</label>
              <input class="form-control" name="payment_amount" type="number" step="0.01" min="0" />
            </div>
            <div class="col-6">
              <label class="form-label">Payment method</label>
              <select class="form-select" name="payment_method">
                <option>Cash</option><option>UPI</option><option>Bank Transfer</option><option>Other</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Start date</label>
              <input class="form-control" name="start_date" type="date" value="<?php echo date('Y-m-d'); ?>" required />
            </div>
          </div>
          <label class="form-label mt-3">Payment reference</label>
          <input class="form-control" name="payment_reference" />
          <label class="form-label mt-3">Payment note</label>
          <textarea class="form-control" name="payment_note" rows="2"></textarea>
          <label class="form-label mt-3">Admin note</label>
          <textarea class="form-control" name="admin_note" rows="2"></textarea>
          <button class="btn btn-primary mt-3" type="submit">Activate Subscription</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Active, Expired and Suspended Subscriptions</h5>
        <div class="table-responsive mt-3">
          <table class="table align-middle">
            <thead><tr><th>Instructor</th><th>Plan</th><th>Payment</th><th>Access</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($subscriptions as $sub): ?>
                <tr>
                  <td><div><?php echo htmlspecialchars($sub['full_name'] ?? 'Unknown'); ?></div><div class="text-muted small"><?php echo htmlspecialchars(($sub['email'] ?? '') . ' ' . ($sub['mobile'] ?? '')); ?></div></td>
                  <td><?php echo htmlspecialchars($sub['plan_name']); ?><div class="text-muted small"><?php echo (int) $sub['duration_days']; ?> days</div></td>
                  <td><?php echo htmlspecialchars((string) $sub['payment_method']); ?><div class="text-muted small"><?php echo htmlspecialchars((string) $sub['payment_reference']); ?></div></td>
                  <td><div><?php echo htmlspecialchars($sub['start_date']); ?></div><div class="text-muted small">to <?php echo htmlspecialchars($sub['expiry_date']); ?></div></td>
                  <td><span class="badge bg-secondary"><?php echo htmlspecialchars($sub['status']); ?></span></td>
                  <td>
                    <div class="d-flex flex-wrap gap-2">
                      <form method="post"><input type="hidden" name="id" value="<?php echo htmlspecialchars($sub['id']); ?>" /><input type="hidden" name="action" value="suspend" /><button class="btn btn-sm btn-outline-warning">Suspend</button></form>
                      <form method="post"><input type="hidden" name="id" value="<?php echo htmlspecialchars($sub['id']); ?>" /><input type="hidden" name="action" value="reactivate" /><button class="btn btn-sm btn-outline-success">Reactivate</button></form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$subscriptions): ?><tr><td colspan="6" class="text-center text-muted">No subscriptions yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>





