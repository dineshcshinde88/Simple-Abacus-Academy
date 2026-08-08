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

function admin_dashboard_amount(PDO $pdo, string $sql): float
{
    try {
        return (float) $pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0.0;
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

$backendPdo = admin_dashboard_backend_pdo($pdo);

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
$totalPaidFees = ($hasLegacySubscriptions && admin_dashboard_column_exists($pdo, 'subscriptions', 'amount') ? admin_dashboard_amount($pdo, "SELECT COALESCE(SUM(amount), 0) FROM subscriptions WHERE payment_status = 'paid'") : 0)
  + ($hasAppSubscriptions && admin_dashboard_column_exists($backendPdo, 'student_subscriptions', 'amount') ? admin_dashboard_amount($backendPdo, "SELECT COALESCE(SUM(amount), 0) FROM student_subscriptions WHERE payment_status = 'paid'") : 0);
$totalUnpaidFees = ($hasLegacySubscriptions && admin_dashboard_column_exists($pdo, 'subscriptions', 'amount') ? admin_dashboard_amount($pdo, "SELECT COALESCE(SUM(amount), 0) FROM subscriptions WHERE payment_status IN ('unpaid', 'pending')") : 0)
  + ($hasAppSubscriptions && admin_dashboard_column_exists($backendPdo, 'student_subscriptions', 'amount') ? admin_dashboard_amount($backendPdo, "SELECT COALESCE(SUM(amount), 0) FROM student_subscriptions WHERE payment_status IN ('unpaid', 'pending')") : 0);
$totalDemos = (int) $pdo->query('SELECT COUNT(*) FROM demo_bookings')->fetchColumn()
  + ($hasAppDemoBookings ? admin_dashboard_count($backendPdo, 'SELECT COUNT(*) FROM demo_bookings') : 0);
$approvedInstructors = $hasAppInstructors ? admin_dashboard_count($backendPdo, "SELECT COUNT(*) FROM instructors WHERE status = 'approved' AND is_verified = 1") : 0;
$totalTeachers = (int) $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn() + $approvedInstructors;
$pendingInstructors = $hasAppInstructors ? admin_dashboard_count($backendPdo, "SELECT COUNT(*) FROM instructors WHERE status = 'pending'") : 0;

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
      <div class="fs-3 fw-bold">₹<?php echo number_format($totalPaidFees, 2); ?></div>
      <div class="text-muted small"><?php echo $totalSubscriptions; ?> paid subscription(s)</div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-metric p-3">
      <div class="text-muted small">Unpaid Fees</div>
      <div class="fs-3 fw-bold">₹<?php echo number_format($totalUnpaidFees, 2); ?></div>
      <div class="text-muted small"><?php echo $totalUnpaidSubscriptions; ?> unpaid/pending subscription(s)</div>
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
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('overviewChart');
  if (ctx && typeof Chart !== 'undefined') {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Students', 'Paid Fees', 'Unpaid Fees', 'Demos', 'Teachers', 'Pending Tutors'],
        datasets: [
          { label: 'Counts', yAxisID: 'yCount', data: [<?php echo $totalStudents; ?>, null, null, <?php echo $totalDemos; ?>, <?php echo $totalTeachers; ?>, <?php echo $pendingInstructors; ?>], backgroundColor: ['#4b1e83', '#4b1e83', '#4b1e83', '#0ea5e9', '#22c55e', '#ef4444'], borderRadius: 8 },
          { label: 'Fee Amount (₹)', yAxisID: 'yFees', data: [null, <?php echo json_encode($totalPaidFees); ?>, <?php echo json_encode($totalUnpaidFees); ?>, null, null, null], backgroundColor: ['#f97316', '#f97316', '#facc15', '#f97316', '#f97316', '#f97316'], borderRadius: 8 }
        ]
      },
      options: {
        plugins: { legend: { display: true } },
        scales: {
          yCount: { beginAtZero: true, position: 'left', title: { display: true, text: 'Count' }, ticks: { precision: 0 } },
          yFees: { beginAtZero: true, position: 'right', title: { display: true, text: 'Fee Amount (₹)' }, grid: { drawOnChartArea: false } }
        }
      }
    });
  }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
