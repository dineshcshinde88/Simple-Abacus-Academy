<?php
$menuItems = [
  ['label' => 'Dashboard', 'href' => 'dashboard.php', 'key' => 'dashboard'],
  ['label' => 'Students', 'href' => 'students.php', 'key' => 'students'],
  ['label' => 'Subscriptions', 'href' => 'subscriptions.php', 'key' => 'subscriptions'],
  ['label' => 'Demo Bookings', 'href' => 'demo_bookings.php', 'key' => 'demo_bookings'],
  ['label' => 'Teachers', 'href' => 'teachers.php', 'key' => 'teachers'],
  ['label' => 'Profile', 'href' => 'profile.php', 'key' => 'profile'],
];
?>
<aside class="admin-sidebar">
  <div class="admin-brand">
    <img
      class="admin-logo"
      src="assets/admin_logo.png"
      alt="Simple Abacus"
    />
  </div>
  <nav class="admin-nav">
    <?php foreach ($menuItems as $item): ?>
      <a class="admin-nav-link <?php echo $activeMenu === $item['key'] ? 'active' : ''; ?>" href="<?php echo $item['href']; ?>">
        <?php echo htmlspecialchars($item['label']); ?>
      </a>
    <?php endforeach; ?>
    <a class="admin-nav-link text-danger" href="logout.php">Logout</a>
  </nav>
</aside>
