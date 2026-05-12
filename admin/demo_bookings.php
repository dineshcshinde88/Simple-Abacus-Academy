<?php
$pageTitle = 'Demo Bookings';
$activeMenu = 'demo_bookings';
require_once __DIR__ . '/includes/header.php';

$success = '';

function admin_demo_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_demo_env_value(string $path, string $key): string
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

function admin_demo_backend_pdo(): ?PDO
{
    $databaseUrl = admin_demo_env_value(__DIR__ . '/../backend/.env', 'DATABASE_URL');
    if ($databaseUrl === '') {
        $databaseUrl = admin_demo_env_value(__DIR__ . '/../.env', 'DATABASE_URL');
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

$backendPdo = admin_demo_backend_pdo();

if (isset($_GET['complete'])) {
    $id = (int) $_GET['complete'];
    $source = $_GET['source'] ?? 'admin';
    $targetPdo = ($source === 'website' && $backendPdo) ? $backendPdo : $pdo;
    $stmt = $targetPdo->prepare("UPDATE demo_bookings SET status = 'completed' WHERE id = ?");
    $stmt->execute([$id]);
    $success = 'Booking marked as completed.';
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $source = $_GET['source'] ?? 'admin';
    $targetPdo = ($source === 'website' && $backendPdo) ? $backendPdo : $pdo;
    $stmt = $targetPdo->prepare('DELETE FROM demo_bookings WHERE id = ?');
    $stmt->execute([$id]);
    $success = 'Booking deleted successfully.';
}

$bookings = [];
foreach ($pdo->query("SELECT *, 'admin' AS source FROM demo_bookings")->fetchAll() as $booking) {
    $bookings[] = $booking;
}

if ($backendPdo && admin_demo_table_exists($backendPdo, 'demo_bookings')) {
    foreach ($backendPdo->query("SELECT *, 'website' AS source FROM demo_bookings")->fetchAll() as $booking) {
        $bookings[] = $booking;
    }
}

usort($bookings, static function (array $a, array $b): int {
    $aDate = $a['created_at'] ?? $a['preferred_date'] ?? '';
    $bDate = $b['created_at'] ?? $b['preferred_date'] ?? '';
    return strtotime((string) $bDate) <=> strtotime((string) $aDate);
});
?>

<?php if ($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <h5 class="card-title">Demo Requests</h5>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Name</th>
            <th>Contact</th>
            <th>Preferred Date</th>
            <th>Status</th>
            <th>Source</th>
            <th>Message</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $booking): ?>
            <tr>
              <td><?php echo htmlspecialchars($booking['name']); ?></td>
              <td>
                <div><?php echo htmlspecialchars($booking['email']); ?></div>
                <div class="text-muted small"><?php echo htmlspecialchars($booking['phone']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($booking['preferred_date']); ?></td>
              <td>
                <span class="badge bg-<?php echo $booking['status'] === 'completed' ? 'success' : 'warning'; ?>">
                  <?php echo htmlspecialchars($booking['status']); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($booking['source'] === 'website' ? 'Website Form' : 'Admin'); ?></td>
              <td style="white-space: pre-line;"><?php echo htmlspecialchars($booking['message']); ?></td>
              <td class="d-flex gap-2">
                <?php if ($booking['status'] !== 'completed'): ?>
                  <a class="btn btn-sm btn-outline-success" href="demo_bookings.php?complete=<?php echo (int) $booking['id']; ?>&source=<?php echo urlencode($booking['source']); ?>">Mark Completed</a>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-danger" href="demo_bookings.php?delete=<?php echo (int) $booking['id']; ?>&source=<?php echo urlencode($booking['source']); ?>" onclick="return confirm('Delete this booking?');">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$bookings): ?>
            <tr><td colspan="7" class="text-center text-muted">No demo bookings found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
