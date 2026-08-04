<?php
$menuItems = [
  ['label' => 'Dashboard', 'href' => 'dashboard.php', 'key' => 'dashboard'],
  ['label' => 'Students', 'href' => 'students.php', 'key' => 'students'],
  ['label' => 'Subscriptions', 'href' => 'subscriptions.php', 'key' => 'subscriptions'],
  ['label' => 'Demo Bookings', 'href' => 'demo_bookings.php', 'key' => 'demo_bookings'],
  ['label' => 'Website Enquiries', 'href' => 'enquiries.php', 'key' => 'enquiries'],
  ['label' => 'Instructors', 'href' => 'instructors.php', 'key' => 'instructors'],
  ['label' => 'Instructor Video Subscriptions', 'href' => 'instructor_video_subscriptions.php', 'key' => 'instructor_video_subscriptions'],
  ['label' => 'Training Video Management', 'href' => 'training_videos.php', 'key' => 'training_videos'],
  ['label' => 'Teachers', 'href' => 'teachers.php', 'key' => 'teachers'],
  ['label' => 'Blog', 'href' => 'blogs.php', 'key' => 'blogs'],
  ['label' => 'Profile', 'href' => 'profile.php', 'key' => 'profile'],
];
?>
<aside class="admin-sidebar">
  <div class="admin-brand">
    <img
      class="admin-logo"
      src="assets/admin_logo.png"
      alt="Simple Abacus"
      onerror="this.onerror=null;this.src='/abacus_logo.svg';"
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
