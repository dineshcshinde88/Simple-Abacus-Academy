<?php
$pageTitle = 'Instructor Shop Orders';
$activeMenu = 'instructor_shop_orders';
require_once __DIR__ . '/includes/header.php';

function shop_orders_pdo(PDO $adminPdo): ?PDO
{
    $env = [];
    foreach ([__DIR__ . '/../backend/.env', __DIR__ . '/../.env'] as $envPath) {
        if (!is_file($envPath)) continue;
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if (!array_key_exists($key, $env)) $env[$key] = trim(trim($value), "\"'");
        }
    }

    $host = '';
    $port = '3306';
    $database = '';
    $username = '';
    $password = '';
    $databaseUrl = trim((string) ($env['DATABASE_URL'] ?? ''));
    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if (!is_array($parts) && preg_match('#^mysql://([^:]+):([^@]*)@([^:/?#]+)(?::([0-9]+))?/([^?]+)#i', $databaseUrl, $match) === 1) {
            $parts = ['user' => urldecode($match[1]), 'pass' => urldecode($match[2]), 'host' => $match[3], 'port' => $match[4] !== '' ? $match[4] : 3306, 'path' => '/' . urldecode($match[5])];
        }
        if (is_array($parts)) {
            $host = (string) ($parts['host'] ?? 'localhost');
            $port = (string) ($parts['port'] ?? '3306');
            $database = trim((string) ($parts['path'] ?? ''), '/');
            $username = urldecode((string) ($parts['user'] ?? ''));
            $password = urldecode((string) ($parts['pass'] ?? ''));
        }
    }

    if ($database === '' || $username === '') {
        $host = trim((string) ($env['DB_HOST'] ?? 'localhost')) ?: 'localhost';
        $port = trim((string) ($env['DB_PORT'] ?? '3306')) ?: '3306';
        $database = trim((string) ($env['DB_DATABASE'] ?? ''));
        $username = trim((string) ($env['DB_USERNAME'] ?? ''));
        $password = (string) ($env['DB_PASSWORD'] ?? '');
    }
    if ($database === '' || $username === '') return null;

    try {
        $adminDatabase = (string) $adminPdo->query('SELECT DATABASE()')->fetchColumn();
        if ($database === $adminDatabase && in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true)) return $adminPdo;
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
    } catch (Throwable $e) {
        error_log('Instructor shop admin database connection failed: ' . $e->getMessage());
        return null;
    }
}

$pdo = shop_orders_pdo($pdo);
$success = '';
$loadError = '';
if ($pdo && isset($_POST['id'], $_POST['courier_status']) && in_array($_POST['courier_status'], ['pending', 'processing', 'dispatched', 'delivered'], true)) {
    $stmt = $pdo->prepare('UPDATE teacher_shop_orders SET metadata_json = JSON_SET(COALESCE(metadata_json, JSON_OBJECT()), "$.courierStatus", ?), updated_at = NOW() WHERE id = ?');
    $stmt->execute([$_POST['courier_status'], $_POST['id']]);
    $success = 'Courier status updated.';
}

$paymentStatus = preg_replace('/[^a-z_]/', '', (string) ($_GET['payment_status'] ?? ''));
$orders = [];
if ($pdo) {
    try {
        $sql = 'SELECT o.*, u.name AS instructor_name, u.email AS instructor_email,
                       i.country_code AS instructor_country_code, i.mobile AS instructor_mobile
                FROM teacher_shop_orders o
                LEFT JOIN users u ON u.id = o.teacher_user_id
                LEFT JOIN instructors i ON LOWER(i.email) = LOWER(u.email)';
        $params = [];
        if ($paymentStatus !== '') { $sql .= ' WHERE o.payment_status = ?'; $params[] = $paymentStatus; }
        $sql .= ' ORDER BY o.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Instructor shop orders could not be loaded: ' . $e->getMessage());
        $loadError = 'Instructor shop orders could not be loaded from the website database.';
    }
}
?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
      <h5 class="card-title mb-0">Instructor Purchases & Courier Queue</h5>
      <form method="get"><select name="payment_status" class="form-select" onchange="this.form.submit()"><option value="">All payments</option><?php foreach (['pending','successful','failed'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $paymentStatus === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option><?php endforeach; ?></select></form>
    </div>
    <?php if (!$pdo): ?><div class="alert alert-warning">Website database is not connected.</div><?php endif; ?>
    <?php if ($loadError): ?><div class="alert alert-danger"><?php echo htmlspecialchars($loadError); ?></div><?php endif; ?>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Invoice</th><th>Instructor</th><th>Purchased Items</th><th>Delivery Details</th><th>Total</th><th>Payment</th><th>Courier</th><th>Date</th></tr></thead><tbody>
      <?php foreach ($orders as $order): $meta = json_decode((string) ($order['metadata_json'] ?? ''), true) ?: []; $items = is_array($meta['items'] ?? null) && $meta['items'] ? $meta['items'] : [[ 'productName' => $order['product_name'], 'category' => $order['category'], 'selectedOption' => $order['selected_option'], 'quantity' => $order['quantity'], 'unitPrice' => $order['unit_price'], 'finalPrice' => $order['final_price'] ]]; $shipping = is_array($meta['shipping'] ?? null) ? $meta['shipping'] : []; $courierStatus = (string) ($meta['courierStatus'] ?? 'pending'); ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($order['invoice_number']); ?></strong><div class="small text-muted"><?php echo htmlspecialchars($order['id']); ?></div></td>
        <td><?php echo htmlspecialchars($order['instructor_name'] ?? 'Unknown'); ?><div class="small text-muted"><?php echo htmlspecialchars($order['instructor_email'] ?? ''); ?></div><?php if (!empty($order['instructor_mobile'])): ?><div class="small text-muted"><?php echo htmlspecialchars(trim((string) ($order['instructor_country_code'] ?? '') . ' ' . (string) $order['instructor_mobile'])); ?></div><?php endif; ?></td>
        <td><ol class="mb-0 ps-3"><?php foreach ($items as $item): ?><li class="mb-2"><strong><?php echo htmlspecialchars((string) ($item['productName'] ?? 'Product')); ?></strong><?php if (!empty($item['category'])): ?><div class="small text-muted">Category: <?php echo htmlspecialchars((string) $item['category']); ?></div><?php endif; ?><div><?php echo htmlspecialchars((string) ($item['optionLabel'] ?? 'Option')); ?>: <?php echo htmlspecialchars((string) ($item['selectedOption'] ?? '')); ?></div><div>Qty: <?php echo (int) ($item['quantity'] ?? 1); ?><?php if (isset($item['unitPrice'])): ?> × ₹<?php echo number_format((float) $item['unitPrice'], 2); ?><?php endif; ?><?php if (isset($item['finalPrice'])): ?> = <strong>₹<?php echo number_format((float) $item['finalPrice'], 2); ?></strong><?php endif; ?></div></li><?php endforeach; ?></ol></td>
        <td><?php if ($shipping): ?><strong><?php echo htmlspecialchars((string) ($shipping['recipientName'] ?? '')); ?></strong><div><?php echo htmlspecialchars((string) ($shipping['phone'] ?? '')); ?></div><div class="small"><?php echo nl2br(htmlspecialchars((string) ($shipping['address'] ?? ''))); ?></div><div class="small"><?php echo htmlspecialchars(trim((string) ($shipping['city'] ?? '') . ', ' . (string) ($shipping['state'] ?? ''), ', ')); ?> - <?php echo htmlspecialchars((string) ($shipping['pincode'] ?? '')); ?></div><?php else: ?><span class="text-muted">Not captured for this older order</span><?php endif; ?></td>
        <td>₹<?php echo number_format((float) $order['final_price'], 2); ?></td>
        <td><span class="badge bg-<?php echo $order['payment_status'] === 'successful' ? 'success' : ($order['payment_status'] === 'failed' ? 'danger' : 'warning text-dark'); ?>"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span><?php if (!empty($order['razorpay_order_id'])): ?><div class="small text-muted">Order: <?php echo htmlspecialchars($order['razorpay_order_id']); ?></div><?php endif; ?><?php if (!empty($order['razorpay_payment_id'])): ?><div class="small text-muted">Payment: <?php echo htmlspecialchars($order['razorpay_payment_id']); ?></div><?php endif; ?></td>
        <td><form method="post"><input type="hidden" name="id" value="<?php echo htmlspecialchars($order['id']); ?>"><select name="courier_status" class="form-select form-select-sm" onchange="this.form.submit()" <?php echo $order['payment_status'] !== 'successful' ? 'disabled' : ''; ?>><?php foreach (['pending','processing','dispatched','delivered'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $courierStatus === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option><?php endforeach; ?></select></form></td>
        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="8" class="text-center text-muted">No instructor shop orders found.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
