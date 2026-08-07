<?php
$pageTitle = 'Website Enquiries';
$activeMenu = 'enquiries';
require_once __DIR__ . '/includes/header.php';

function enquiry_env_value(array $paths, string $key): string
{
    foreach ($paths as $path) {
        if (!is_file($path)) {
            continue;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$lineKey, $value] = explode('=', $line, 2);
            if (trim($lineKey) === $key) {
                return trim(trim($value), "\"'");
            }
        }
    }
    return '';
}

function enquiry_backend_pdo(PDO $adminPdo, ?string &$error = null): ?PDO
{
    $databaseUrl = enquiry_env_value([
        __DIR__ . '/../backend/.env',
        __DIR__ . '/../.env',
    ], 'DATABASE_URL');

    if ($databaseUrl === '') {
        $error = 'DATABASE_URL is missing. Configure the admin server to use the same database as api.simpleabacus.com.';
        return null;
    }

    $parts = parse_url($databaseUrl);
    if (!is_array($parts) || empty($parts['path']) || !array_key_exists('user', $parts)) {
        $error = 'DATABASE_URL is invalid.';
        return null;
    }

    $host = (string) ($parts['host'] ?? 'localhost');
    $port = (string) ($parts['port'] ?? '3306');
    $database = trim((string) $parts['path'], '/');
    $username = urldecode((string) ($parts['user'] ?? ''));
    $password = urldecode((string) ($parts['pass'] ?? ''));

    try {
        $adminDatabase = (string) $adminPdo->query('SELECT DATABASE()')->fetchColumn();
        if ($adminDatabase === $database && in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true)) {
            return $adminPdo;
        }
    } catch (Throwable $e) {
        // Continue with the explicit backend connection.
    }

    try {
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (Throwable $e) {
        $error = 'Unable to connect to the website enquiries database: ' . $e->getMessage();
        return null;
    }
}

function enquiry_ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS website_enquiries (
            id CHAR(36) PRIMARY KEY,
            enquiry_type VARCHAR(40) NOT NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NULL,
            phone VARCHAR(20) NULL,
            subject VARCHAR(180) NULL,
            message TEXT NULL,
            details_json LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_website_enquiries_type_status (enquiry_type, status),
            INDEX idx_website_enquiries_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

$labels = [
    'contact' => 'Contact',
    'franchise' => 'Partner / Franchise',
    'teacher_training' => 'Teacher Training',
    'chatbot' => 'Chatbot',
];
$allowedStatuses = ['pending', 'contacted', 'completed'];
$success = '';
$errors = [];
$connectionError = null;
$backendPdo = enquiry_backend_pdo($pdo, $connectionError);

if ($backendPdo) {
    try {
        enquiry_ensure_schema($backendPdo);
    } catch (Throwable $e) {
        $connectionError = 'The enquiries table is unavailable: ' . $e->getMessage();
        $backendPdo = null;
    }
}

if (empty($_SESSION['enquiry_csrf'])) {
    $_SESSION['enquiry_csrf'] = bin2hex(random_bytes(24));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals((string) $_SESSION['enquiry_csrf'], $csrf)) {
        $errors[] = 'Your session expired. Please refresh and try again.';
    } elseif (!$backendPdo) {
        $errors[] = $connectionError ?: 'Website enquiries database is not connected.';
    } else {
        $id = trim((string) ($_POST['id'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? ''));
        if ($id === '' || !in_array($status, $allowedStatuses, true)) {
            $errors[] = 'Invalid enquiry status update.';
        } else {
            try {
                $stmt = $backendPdo->prepare('UPDATE website_enquiries SET status = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$status, $id]);
                $success = $stmt->rowCount() > 0 ? 'Enquiry status updated.' : 'Enquiry was not found.';
            } catch (Throwable $e) {
                $errors[] = 'Unable to update enquiry status: ' . $e->getMessage();
            }
        }
    }
}

$type = preg_replace('/[^a-z_]/', '', (string) ($_GET['type'] ?? ''));
$statusFilter = preg_replace('/[^a-z_]/', '', (string) ($_GET['status'] ?? ''));
if ($type !== '' && !array_key_exists($type, $labels)) {
    $type = '';
}
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
if ($type !== '') {
    $where[] = 'enquiry_type = ?';
    $params[] = $type;
}
if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$enquiries = [];
$counts = ['total' => 0, 'pending' => 0, 'contact' => 0];

if ($backendPdo) {
    try {
        $stmt = $backendPdo->prepare('SELECT * FROM website_enquiries' . $whereSql . ' ORDER BY created_at DESC');
        $stmt->execute($params);
        $enquiries = $stmt->fetchAll();

        $countRow = $backendPdo->query(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'pending') AS pending,
                    SUM(enquiry_type = 'contact') AS contact
             FROM website_enquiries"
        )->fetch();
        $counts = [
            'total' => (int) ($countRow['total'] ?? 0),
            'pending' => (int) ($countRow['pending'] ?? 0),
            'contact' => (int) ($countRow['contact'] ?? 0),
        ];
    } catch (Throwable $e) {
        $errors[] = 'Unable to load website enquiries: ' . $e->getMessage();
    }
}
?>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($connectionError): ?>
  <div class="alert alert-danger">
    <strong>Website enquiries are not connected.</strong>
    <div><?php echo htmlspecialchars($connectionError); ?></div>
  </div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $error): ?><div><?php echo htmlspecialchars($error); ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">All Enquiries</div><div class="h3 mb-0"><?php echo $counts['total']; ?></div></div></div></div>
  <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Pending</div><div class="h3 mb-0 text-warning"><?php echo $counts['pending']; ?></div></div></div></div>
  <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Contact Form Messages</div><div class="h3 mb-0 text-primary"><?php echo $counts['contact']; ?></div></div></div></div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
      <div>
        <h5 class="card-title mb-1">Website Enquiries</h5>
        <div class="text-muted small">Messages submitted through Contact Us and other website enquiry forms.</div>
      </div>
      <form method="get" class="d-flex flex-wrap gap-2">
        <select name="type" class="form-select">
          <option value="">All types</option>
          <?php foreach ($labels as $value => $label): ?>
            <option value="<?php echo $value; ?>" <?php echo $type === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <?php foreach ($allowedStatuses as $status): ?>
            <option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($status)); ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary" type="submit">Filter</button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Type</th><th>Name</th><th>Contact</th><th>Subject / Message</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($enquiries as $item): ?><tr>
            <td><span class="badge text-bg-light"><?php echo htmlspecialchars($labels[$item['enquiry_type']] ?? $item['enquiry_type']); ?></span></td>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><div><?php echo htmlspecialchars($item['email'] ?? ''); ?></div><div class="text-muted small"><?php echo htmlspecialchars($item['phone'] ?? ''); ?></div></td>
            <td><strong><?php echo htmlspecialchars($item['subject'] ?? ''); ?></strong><div style="white-space:pre-line"><?php echo htmlspecialchars($item['message'] ?? ''); ?></div></td>
            <td><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime((string) $item['created_at']))); ?></td>
            <td>
              <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars((string) $_SESSION['enquiry_csrf']); ?>" />
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>" />
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php foreach ($allowedStatuses as $status): ?><option value="<?php echo $status; ?>" <?php echo $item['status'] === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($status)); ?></option><?php endforeach; ?>
                </select>
              </form>
            </td>
          </tr><?php endforeach; ?>
          <?php if (!$enquiries): ?><tr><td colspan="6" class="text-center text-muted py-4"><?php echo $backendPdo ? 'No enquiries match the selected filters.' : 'Enquiries cannot be loaded until the website database is connected.'; ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>