<?php
$pageTitle = 'Website Enquiries';
$activeMenu = 'enquiries';
require_once __DIR__ . '/includes/header.php';

function enquiry_backend_pdo(): ?PDO
{
    $envPath = __DIR__ . '/../backend/.env';
    if (!is_file($envPath)) return null;
    $databaseUrl = '';
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), 'DATABASE_URL=')) {
            $databaseUrl = trim(substr(trim($line), strlen('DATABASE_URL=')), " \t\n\r\0\x0B\"'");
            break;
        }
    }
    $parts = $databaseUrl !== '' ? parse_url($databaseUrl) : false;
    if (!is_array($parts) || empty($parts['path']) || empty($parts['user'])) return null;
    try {
        return new PDO(
            'mysql:host=' . ($parts['host'] ?? 'localhost') . ';port=' . ($parts['port'] ?? 3306) . ';dbname=' . trim($parts['path'], '/') . ';charset=utf8mb4',
            urldecode($parts['user']),
            urldecode($parts['pass'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) { return null; }
}

$backendPdo = enquiry_backend_pdo();
$success = '';
if ($backendPdo && isset($_POST['id'], $_POST['status']) && in_array($_POST['status'], ['pending', 'contacted', 'completed'], true)) {
    $stmt = $backendPdo->prepare('UPDATE website_enquiries SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$_POST['status'], $_POST['id']]);
    $success = 'Enquiry status updated.';
}
$type = preg_replace('/[^a-z_]/', '', (string) ($_GET['type'] ?? ''));
$params = [];
$where = '';
if ($type !== '') { $where = ' WHERE enquiry_type = ?'; $params[] = $type; }
$enquiries = [];
if ($backendPdo) {
    try {
        $stmt = $backendPdo->prepare('SELECT * FROM website_enquiries' . $where . ' ORDER BY created_at DESC');
        $stmt->execute($params);
        $enquiries = $stmt->fetchAll();
    } catch (Throwable $e) { $enquiries = []; }
}
$labels = ['contact' => 'Contact', 'franchise' => 'Partner / Franchise', 'teacher_training' => 'Teacher Training', 'chatbot' => 'Chatbot'];
?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
      <h5 class="card-title mb-0">Enquiries</h5>
      <form method="get"><select name="type" class="form-select" onchange="this.form.submit()"><option value="">All types</option><?php foreach ($labels as $value => $label): ?><option value="<?php echo $value; ?>" <?php echo $type === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select></form>
    </div>
    <?php if (!$backendPdo): ?><div class="alert alert-warning">Website database is not connected.</div><?php endif; ?>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Type</th><th>Name</th><th>Contact</th><th>Subject / Message</th><th>Date</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($enquiries as $item): ?><tr>
        <td><?php echo htmlspecialchars($labels[$item['enquiry_type']] ?? $item['enquiry_type']); ?></td>
        <td><?php echo htmlspecialchars($item['name']); ?></td>
        <td><div><?php echo htmlspecialchars($item['email'] ?? ''); ?></div><div class="text-muted small"><?php echo htmlspecialchars($item['phone'] ?? ''); ?></div></td>
        <td><strong><?php echo htmlspecialchars($item['subject'] ?? ''); ?></strong><div style="white-space:pre-line"><?php echo htmlspecialchars($item['message'] ?? ''); ?></div></td>
        <td><?php echo htmlspecialchars($item['created_at']); ?></td>
        <td><form method="post"><input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><?php foreach (['pending', 'contacted', 'completed'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $item['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option><?php endforeach; ?></select></form></td>
      </tr><?php endforeach; ?>
      <?php if (!$enquiries): ?><tr><td colspan="6" class="text-center text-muted">No enquiries found.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
