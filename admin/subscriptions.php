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

function admin_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_first_existing_column(PDO $pdo, string $table, array $columns): ?string
{
    foreach ($columns as $column) {
        if (admin_column_exists($pdo, $table, $column)) {
            return $column;
        }
    }
    return null;
}

function admin_student_phone_map(PDO $pdo): array
{
    if (!admin_table_exists($pdo, 'students') || !admin_column_exists($pdo, 'students', 'email')) {
        return [];
    }

    $phoneColumn = admin_first_existing_column($pdo, 'students', ['phone', 'mobile', 'mobile_number', 'mobileNumber']);
    if ($phoneColumn === null) {
        return [];
    }
    $countryColumn = admin_first_existing_column($pdo, 'students', ['phone_country', 'phoneCountry', 'country_code', 'countryCode']);
    $countrySelect = $countryColumn !== null ? "`{$countryColumn}`" : "'+91'";
    $rows = $pdo->query(
        "SELECT LOWER(TRIM(email)) AS email, TRIM(`{$phoneColumn}`) AS phone, {$countrySelect} AS phone_country
         FROM students
         WHERE COALESCE(NULLIF(TRIM(email), ''), '') <> ''
           AND COALESCE(NULLIF(TRIM(`{$phoneColumn}`), ''), '') <> ''"
    )->fetchAll();

    $map = [];
    foreach ($rows as $row) {
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $phone = trim((string) ($row['phone'] ?? ''));
        $country = trim((string) ($row['phone_country'] ?? ''));
        if ($email !== '' && $phone !== '') {
            $map[$email] = trim(($country !== '' ? $country : '+91') . ' ' . $phone);
        }
    }
    return $map;
}

function admin_fill_subscription_phones(array $subscriptions, array $phoneMap): array
{
    foreach ($subscriptions as &$subscription) {
        $currentPhone = trim((string) ($subscription['student_phone'] ?? ''));
        if ($currentPhone !== '' && $currentPhone !== '-') {
            continue;
        }
        $email = strtolower(trim((string) ($subscription['student_email'] ?? '')));
        if ($email !== '' && isset($phoneMap[$email])) {
            $subscription['student_phone'] = $phoneMap[$email];
        }
    }
    unset($subscription);
    return $subscriptions;
}

function admin_database_name(PDO $pdo): string
{
    try {
        return (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    } catch (Throwable $e) {
        return '';
    }
}

function admin_env_value(string $path, string $key): string
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

function admin_backend_pdo(PDO $adminPdo): ?PDO
{
    $databaseUrl = admin_env_value(__DIR__ . '/../backend/.env', 'DATABASE_URL');
    if ($databaseUrl === '') {
        $databaseUrl = admin_env_value(__DIR__ . '/../.env', 'DATABASE_URL');
    }
    if ($databaseUrl === '') {
        return null;
    }

    $parts = parse_url($databaseUrl);
    if (!is_array($parts) && preg_match('#^mysql://([^:]+):([^@]*)@([^:/?#]+)(?::([0-9]+))?/([^?]+)#i', $databaseUrl, $match) === 1) {
        $parts = ['user' => urldecode($match[1]), 'pass' => urldecode($match[2]), 'host' => $match[3], 'port' => $match[4] !== '' ? $match[4] : 3306, 'path' => '/' . urldecode($match[5])];
    }
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

    if ($db === admin_database_name($adminPdo)) {
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

function admin_subscription_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function admin_assignment_type_label(?string $notes): string
{
    $notes = strtolower((string) $notes);
    if (str_contains($notes, 'free_scholarship')) {
        return 'Free / Scholarship';
    }
    if (str_contains($notes, 'offline_paid')) {
        return 'Offline Paid';
    }
    return 'Online Payment';
}
$backendPdo = admin_backend_pdo($pdo);
$subscriptionPdo = $backendPdo ?: $pdo;
$hasNewSubscriptions = admin_table_exists($subscriptionPdo, 'student_subscriptions')
    && admin_table_exists($subscriptionPdo, 'users')
    && admin_table_exists($subscriptionPdo, 'students');

if ($hasNewSubscriptions && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'assign_worksheet') {
        $studentId = trim((string) ($_POST['student_id'] ?? ''));
        $planId = trim((string) ($_POST['plan_id'] ?? ''));
        $assignmentType = trim((string) ($_POST['assignment_type'] ?? ''));
        $startInput = trim((string) ($_POST['start_date'] ?? ''));
        $expiryInput = trim((string) ($_POST['expiry_date'] ?? ''));
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $remark = trim((string) ($_POST['remark'] ?? ''));

        if ($studentId === '' || $planId === '' || !in_array($assignmentType, ['offline_paid', 'free_scholarship'], true)) {
            $errors[] = 'Please select a student, worksheet plan, and assignment type.';
        }

        $startTs = strtotime($startInput . ' 00:00:00');
        $expiryTs = strtotime($expiryInput . ' 23:59:59');
        if ($startInput === '' || $expiryInput === '' || $startTs === false || $expiryTs === false || $expiryTs < $startTs) {
            $errors[] = 'Please enter a valid subscription date range.';
        }

        $student = null;
        $plan = null;
        if (!$errors) {
            $studentStmt = $subscriptionPdo->prepare(
                'SELECT st.id, u.name, u.email FROM students st INNER JOIN users u ON u.id = st.user_id WHERE st.id = ? LIMIT 1'
            );
            $studentStmt->execute([$studentId]);
            $student = $studentStmt->fetch();

            $planStmt = $subscriptionPdo->prepare(
                "SELECT p.*, l.level_name, c.slug AS course_slug, c.name AS course_name
                 FROM subscription_plans p
                 INNER JOIN levels l ON l.id = p.level_id
                 INNER JOIN courses c ON c.id = l.course_id
                 WHERE p.id = ? AND p.is_active = 1
                   AND c.slug IN ('abacus-worksheet', 'vedic-maths-worksheet')
                 LIMIT 1"
            );
            $planStmt->execute([$planId]);
            $plan = $planStmt->fetch();

            if (!$student) {
                $errors[] = 'Registered student not found.';
            }
            if (!$plan) {
                $errors[] = 'Active Abacus or Vedic Maths worksheet plan not found.';
            }
        }

        if (!$errors && $student && $plan) {
            $now = gmdate('Y-m-d H:i:s');
            $startDate = $startInput . ' 00:00:00';
            $expiryDate = $assignmentType === 'free_scholarship'
                ? gmdate('Y-m-d 23:59:59', strtotime($startDate . ' +90 days'))
                : $expiryInput . ' 23:59:59';
            $assignedAmount = $assignmentType === 'free_scholarship' ? 0 : $amount;
            $notes = 'admin_assignment_type=' . $assignmentType
                . '; assigned_by=' . ($adminName ?: 'Admin')
                . '; assigned_at=' . $now
                . ($remark !== '' ? '; remark=' . str_replace(["\r", "\n", ';'], ' ', $remark) : '');

            try {
                $subscriptionPdo->beginTransaction();
                $expireStmt = $subscriptionPdo->prepare(
                    "UPDATE student_subscriptions
                     SET status = 'expired', updated_at = ?
                     WHERE student_id = ? AND level_id = ? AND status = 'active'"
                );
                $expireStmt->execute([$now, $studentId, $plan['level_id']]);

                $insertStmt = $subscriptionPdo->prepare(
                    'INSERT INTO student_subscriptions
                     (id, student_id, plan_id, level_id, plan_name, amount, currency, start_date, expiry_date, status, payment_status, notes, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertStmt->execute([
                    admin_subscription_uuid(),
                    $studentId,
                    $plan['id'],
                    $plan['level_id'],
                    $plan['name'],
                    $assignedAmount,
                    $plan['currency'] ?: 'INR',
                    $startDate,
                    $expiryDate,
                    'active',
                    'paid',
                    $notes,
                    $now,
                    $now,
                ]);
                $subscriptionPdo->commit();
                $success = sprintf('%s assigned to %s successfully.', $plan['level_name'], $student['name']);
            } catch (Throwable $e) {
                if ($subscriptionPdo->inTransaction()) {
                    $subscriptionPdo->rollBack();
                }
                $errors[] = 'Unable to assign the worksheet subscription. Please try again.';
            }
        }
    } elseif ($action === 'revoke_worksheet') {
        $subscriptionId = trim((string) ($_POST['subscription_id'] ?? ''));
        if ($subscriptionId === '') {
            $errors[] = 'Subscription is required.';
        } else {
            $revokeStmt = $subscriptionPdo->prepare(
                "UPDATE student_subscriptions
                 SET status = 'suspended', updated_at = ?
                 WHERE id = ? AND notes LIKE 'admin_assignment_type=%'"
            );
            $revokeStmt->execute([gmdate('Y-m-d H:i:s'), $subscriptionId]);
            if ($revokeStmt->rowCount() > 0) {
                $success = 'Worksheet access revoked successfully.';
            } else {
                $errors[] = 'Only active, manually assigned worksheet access can be revoked here.';
            }
        }
    }
}
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
$activeCount = 0;
$paidCount = 0;
$freeCount = 0;

if ($hasNewSubscriptions) {
    $studentPhoneColumn = admin_first_existing_column($subscriptionPdo, 'students', ['phone', 'mobile', 'mobile_number', 'mobileNumber']);
    $studentPhoneCountryColumn = admin_first_existing_column($subscriptionPdo, 'students', ['phone_country', 'phoneCountry', 'country_code', 'countryCode']);
    $studentPhoneSelect = $studentPhoneColumn !== null
        ? ($studentPhoneCountryColumn !== null
            ? "CASE WHEN COALESCE(NULLIF(TRIM(st.`{$studentPhoneColumn}`), ''), '') = '' THEN '-' ELSE TRIM(CONCAT(COALESCE(NULLIF(TRIM(st.`{$studentPhoneCountryColumn}`), ''), '+91'), ' ', st.`{$studentPhoneColumn}`)) END"
            : "COALESCE(NULLIF(TRIM(st.`{$studentPhoneColumn}`), ''), '-')")
        : "'-'";
    $studentStmt = $subscriptionPdo->query(
        "SELECT st.id, u.name, u.email, {$studentPhoneSelect} AS phone
         FROM students st
         INNER JOIN users u ON u.id = st.user_id
         ORDER BY u.name ASC, u.email ASC"
    );
    $students = $studentStmt->fetchAll();

    $planStmt = $subscriptionPdo->query(
        "SELECT p.id, p.name, p.price, p.currency, p.duration_days, l.level_name, c.name AS course_name, c.slug AS course_slug
         FROM subscription_plans p
         INNER JOIN levels l ON l.id = p.level_id
         INNER JOIN courses c ON c.id = l.course_id
         WHERE p.is_active = 1 AND c.slug IN ('abacus-worksheet', 'vedic-maths-worksheet')
         ORDER BY c.name ASC, l.level_name ASC, p.duration_days ASC"
    );
    $worksheetPlans = $planStmt->fetchAll();

    $summaryStmt = $subscriptionPdo->query(
        "SELECT
            SUM(CASE WHEN ss.status = 'active' AND ss.expiry_date >= UTC_TIMESTAMP() THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN ss.payment_status = 'paid' AND COALESCE(ss.notes, '') NOT LIKE '%admin_assignment_type=free_scholarship%' THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN COALESCE(ss.notes, '') LIKE '%admin_assignment_type=free_scholarship%' AND ss.status = 'active' AND ss.expiry_date >= UTC_TIMESTAMP() THEN 1 ELSE 0 END) AS free_count
         FROM student_subscriptions ss
         LEFT JOIN levels l ON l.id = ss.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         WHERE c.slug IN ('abacus-worksheet', 'vedic-maths-worksheet')"
    );
    $summary = $summaryStmt->fetch() ?: [];
    $activeCount = (int) ($summary['active_count'] ?? 0);
    $paidCount = (int) ($summary['paid_count'] ?? 0);
    $freeCount = (int) ($summary['free_count'] ?? 0);

    $where = "WHERE c.slug IN ('abacus-worksheet', 'vedic-maths-worksheet')";
    $params = [];
    if ($statusFilter === 'paid') {
        $where .= " AND ss.payment_status = 'paid' AND COALESCE(ss.notes, '') NOT LIKE '%admin_assignment_type=free_scholarship%'";
    } elseif ($statusFilter === 'free') {
        $where .= " AND COALESCE(ss.notes, '') LIKE '%admin_assignment_type=free_scholarship%'";
    } elseif ($statusFilter === 'active') {
        $where .= " AND ss.status = 'active' AND ss.expiry_date >= UTC_TIMESTAMP()";
    } elseif ($statusFilter === 'expired') {
        $where .= " AND (ss.status <> 'active' OR ss.expiry_date < UTC_TIMESTAMP())";
    }

    $sql = "
        SELECT
            ss.*,
            u.name AS student_name,
            u.email AS student_email,
            {$studentPhoneSelect} AS student_phone,
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
    $listStmt = $subscriptionPdo->prepare($sql);
    $listStmt->execute($params);
    $subscriptions = $listStmt->fetchAll();
    $subscriptions = admin_fill_subscription_phones($subscriptions, admin_student_phone_map($pdo));
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

    $legacyPhoneColumn = admin_first_existing_column($pdo, 'students', ['phone', 'mobile', 'mobile_number', 'mobileNumber']);
    $legacyCountryColumn = admin_first_existing_column($pdo, 'students', ['phone_country', 'phoneCountry', 'country_code', 'countryCode']);
    $legacyPhoneSelect = $legacyPhoneColumn !== null
        ? ($legacyCountryColumn !== null
            ? "CASE WHEN COALESCE(NULLIF(TRIM(st.`{$legacyPhoneColumn}`), ''), '') = '' THEN '-' ELSE TRIM(CONCAT(COALESCE(NULLIF(TRIM(st.`{$legacyCountryColumn}`), ''), '+91'), ' ', st.`{$legacyPhoneColumn}`)) END"
            : "COALESCE(NULLIF(TRIM(st.`{$legacyPhoneColumn}`), ''), '-')")
        : "'-'";
    $listStmt = $pdo->prepare("SELECT s.*, st.name AS student_name, st.email AS student_email, {$legacyPhoneSelect} AS student_phone FROM subscriptions s JOIN students st ON s.student_id = st.id {$where} ORDER BY s.id DESC");
    $listStmt->execute($params);
    $subscriptions = $listStmt->fetchAll();
    $subscriptions = admin_fill_subscription_phones($subscriptions, admin_student_phone_map($pdo));
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
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
          <h5 class="card-title mb-1">Assign Worksheet Subscription</h5>
          <div class="text-muted small">Manually unlock Abacus or Vedic Maths worksheets after an offline payment, or provide free scholarship access.</div>
        </div>
        <span class="badge text-bg-primary">Admin Access</span>
      </div>

      <?php if (!$students || !$worksheetPlans): ?>
        <div class="alert alert-warning mb-0">Registered students or active worksheet plans are not available yet.</div>
      <?php else: ?>
        <form method="post" class="row g-3" id="worksheet-assignment-form">
          <input type="hidden" name="action" value="assign_worksheet" />
          <div class="col-lg-4">
            <label class="form-label">Registered Student <span class="text-danger">*</span></label>
            <select name="student_id" class="form-select" required>
              <option value="">Select student</option>
              <?php foreach ($students as $student): ?>
                <option value="<?php echo htmlspecialchars((string) $student['id']); ?>">
                  <?php echo htmlspecialchars(($student['name'] ?? 'Student') . ' (' . ($student['phone'] ?? '-') . ' / ' . ($student['email'] ?? '-') . ')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-4">
            <label class="form-label">Worksheet &amp; Level <span class="text-danger">*</span></label>
            <select name="plan_id" id="assignment-plan" class="form-select" required>
              <option value="">Select worksheet level and duration</option>
              <?php foreach ($worksheetPlans as $plan): ?>
                <option value="<?php echo htmlspecialchars((string) $plan['id']); ?>" data-price="<?php echo htmlspecialchars((string) ($plan['price'] ?? 0)); ?>" data-duration="<?php echo htmlspecialchars((string) ($plan['duration_days'] ?? 0)); ?>">
                  <?php echo htmlspecialchars(($plan['course_name'] ?? 'Worksheet') . ' - ' . ($plan['level_name'] ?? '-') . ' - ' . admin_duration_label((int) ($plan['duration_days'] ?? 0))); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-4">
            <label class="form-label">Assignment Type <span class="text-danger">*</span></label>
            <select name="assignment_type" class="form-select" id="assignment-type" required>
              <option value="offline_paid">Paid - External / Offline Payment</option>
              <option value="free_scholarship">Unpaid (Free) - 3 Months</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" id="assignment-start" class="form-control" value="<?php echo htmlspecialchars(date('Y-m-d')); ?>" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
            <input type="date" name="expiry_date" id="assignment-expiry" class="form-control" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime('+90 days'))); ?>" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Amount Received</label>
            <div class="input-group">
              <span class="input-group-text">INR</span>
              <input type="number" name="amount" id="assignment-amount" min="0" step="0.01" class="form-control" value="0" />
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Admin Remark</label>
            <input type="text" name="remark" maxlength="500" class="form-control" placeholder="Receipt/reference or reason" />
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-primary px-4" type="submit">Assign Worksheet Access</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="text-muted small">Active Subscriptions</div><div class="fs-3 fw-semibold"><?php echo $activeCount; ?></div></div></div>
        <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="text-muted small">Paid Subscriptions</div><div class="fs-3 fw-semibold text-success"><?php echo $paidCount; ?></div></div></div>
        <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="text-muted small">Unpaid (Free) - Active</div><div class="fs-3 fw-semibold text-primary"><?php echo $freeCount; ?></div></div></div>
      </div>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
          <h5 class="card-title mb-1">Abacus &amp; Vedic Maths Worksheet Subscriptions</h5>
          <div class="text-muted small">Online purchases and manual admin assignments are listed together.</div>
        </div>
        <form method="get" class="d-flex gap-2">
          <select name="status" class="form-select">
            <option value="">All Subscriptions</option>
            <?php foreach (['active' => 'Active', 'paid' => 'Paid', 'free' => 'Unpaid (Free)', 'expired' => 'Expired'] as $status => $statusLabel): ?>
              <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($statusLabel); ?>
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
              <th>Mobile</th>
              <th>Program</th>
              <th>Level</th>
              <th>Access Type</th>
              <th>Start</th>
              <th>Expiry</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Access</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subscriptions as $sub): ?>
              <?php
                $isManual = str_starts_with((string) ($sub['notes'] ?? ''), 'admin_assignment_type=');
                $isFree = str_contains((string) ($sub['notes'] ?? ''), 'admin_assignment_type=free_scholarship');
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($sub['student_name'] ?? '-'); ?></div>
                  <div class="text-muted small"><?php echo htmlspecialchars($sub['student_email'] ?? '-'); ?></div>
                </td>
                <td><?php echo htmlspecialchars($sub['student_phone'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($sub['course_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($sub['level_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars(admin_assignment_type_label($sub['notes'] ?? null)); ?></td>
                <td><?php echo htmlspecialchars(admin_format_date($sub['start_date'] ?? null)); ?></td>
                <td><?php echo htmlspecialchars(admin_format_date($sub['expiry_date'] ?? null)); ?></td>
                <td><?php echo htmlspecialchars(($sub['currency'] ?? 'INR') . ' ' . number_format((float) ($sub['amount'] ?? 0), 2)); ?></td>
                <td>
                  <span class="badge bg-<?php echo $isFree ? 'primary' : admin_payment_status_badge($sub['payment_status'] ?? null); ?>">
                    <?php echo htmlspecialchars($isFree ? 'Unpaid (Free)' : admin_payment_status_label($sub['payment_status'] ?? null)); ?>
                  </span>
                </td>
                <td>
                  <?php $accessActive = ($sub['status'] ?? '') === 'active' && strtotime((string) ($sub['expiry_date'] ?? '')) >= time(); ?>
                  <span class="badge bg-<?php echo $accessActive ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars($accessActive ? 'Active' : ucfirst((string) ($sub['status'] ?? 'expired'))); ?>
                  </span>
                </td>
                <td>
                  <?php if ($isManual && ($sub['status'] ?? '') === 'active'): ?>
                    <form method="post" onsubmit="return confirm('Revoke this manually assigned worksheet access?');">
                      <input type="hidden" name="action" value="revoke_worksheet" />
                      <input type="hidden" name="subscription_id" value="<?php echo htmlspecialchars((string) $sub['id']); ?>" />
                      <button class="btn btn-sm btn-outline-danger" type="submit">Revoke</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$subscriptions): ?>
              <tr><td colspan="11" class="text-center text-muted py-4">No worksheet subscriptions found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    (() => {
      const type = document.getElementById('assignment-type');
      const plan = document.getElementById('assignment-plan');
      const amount = document.getElementById('assignment-amount');
      const start = document.getElementById('assignment-start');
      const expiry = document.getElementById('assignment-expiry');
      if (!type || !plan || !amount || !start || !expiry) return;
      const selectedPlan = () => plan.options[plan.selectedIndex];
      const syncAmount = () => {
        const isFree = type.value === 'free_scholarship';
        amount.disabled = isFree;
        amount.value = isFree ? '0' : (selectedPlan()?.dataset.price || '0');
      };
      const syncExpiry = () => {
        const days = type.value === 'free_scholarship' ? 90 : Number(selectedPlan()?.dataset.duration || 0);
        if (!start.value || days <= 0) return;
        const date = new Date(`${start.value}T00:00:00`);
        date.setDate(date.getDate() + days);
        expiry.value = date.toISOString().slice(0, 10);
      };
      type.addEventListener('change', () => {
        syncAmount();
        syncExpiry();
      });
      plan.addEventListener('change', () => {
        syncAmount();
        syncExpiry();
      });
      start.addEventListener('change', syncExpiry);
      syncAmount();
      syncExpiry();
    })();
  </script>
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
