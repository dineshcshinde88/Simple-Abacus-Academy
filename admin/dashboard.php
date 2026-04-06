<?php
$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$totalStudents = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalSubscriptions = (int) $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE payment_status = 'paid'")->fetchColumn();
$totalDemos = (int) $pdo->query('SELECT COUNT(*) FROM demo_bookings')->fetchColumn();
$totalTeachers = (int) $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();

$activitySql = "
  (SELECT 'Student' AS type, name AS title, created_at AS created_at FROM students)
  UNION ALL
  (SELECT 'Subscription' AS type, plan_name AS title, start_date AS created_at FROM subscriptions)
  UNION ALL
  (SELECT 'Demo Booking' AS type, name AS title, preferred_date AS created_at FROM demo_bookings)
  UNION ALL
  (SELECT 'Teacher' AS type, name AS title, joining_date AS created_at FROM teachers)
  ORDER BY created_at DESC
  LIMIT 5
";
$recentActivities = $pdo->query($activitySql)->fetchAll();
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
      <div class="text-muted small">Paid Subscriptions</div>
      <div class="fs-3 fw-bold"><?php echo $totalSubscriptions; ?></div>
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
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Recent Activities</h5>
        <div class="list-group list-group-flush">
          <?php if (empty($recentActivities)): ?>
            <div class="text-muted">No recent activity found.</div>
          <?php else: ?>
            <?php foreach ($recentActivities as $activity): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold"><?php echo htmlspecialchars($activity['title']); ?></div>
                  <div class="text-muted small"><?php echo htmlspecialchars($activity['type']); ?></div>
                </div>
                <div class="text-muted small">
                  <?php echo htmlspecialchars($activity['created_at']); ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Overview Chart</h5>
        <canvas id="overviewChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
  const ctx = document.getElementById('overviewChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Students', 'Paid Subs', 'Demos', 'Teachers'],
        datasets: [{
          label: 'Counts',
          data: [<?php echo $totalStudents; ?>, <?php echo $totalSubscriptions; ?>, <?php echo $totalDemos; ?>, <?php echo $totalTeachers; ?>],
          backgroundColor: ['#4b1e83', '#f97316', '#0ea5e9', '#22c55e'],
          borderRadius: 8
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
