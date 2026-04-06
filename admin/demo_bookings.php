<?php
$pageTitle = 'Demo Bookings';
$activeMenu = 'demo_bookings';
require_once __DIR__ . '/includes/header.php';

$success = '';

if (isset($_GET['complete'])) {
    $id = (int) $_GET['complete'];
    $stmt = $pdo->prepare("UPDATE demo_bookings SET status = 'completed' WHERE id = ?");
    $stmt->execute([$id]);
    $success = 'Booking marked as completed.';
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM demo_bookings WHERE id = ?');
    $stmt->execute([$id]);
    $success = 'Booking deleted successfully.';
}

$bookings = $pdo->query('SELECT * FROM demo_bookings ORDER BY id DESC')->fetchAll();
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
              <td><?php echo htmlspecialchars($booking['message']); ?></td>
              <td class="d-flex gap-2">
                <?php if ($booking['status'] !== 'completed'): ?>
                  <a class="btn btn-sm btn-outline-success" href="demo_bookings.php?complete=<?php echo (int) $booking['id']; ?>">Mark Completed</a>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-danger" href="demo_bookings.php?delete=<?php echo (int) $booking['id']; ?>" onclick="return confirm('Delete this booking?');">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$bookings): ?>
            <tr><td colspan="6" class="text-center text-muted">No demo bookings found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
