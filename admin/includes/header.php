<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Admin Dashboard';
$activeMenu = $activeMenu ?? '';

$adminStmt = $pdo->prepare('SELECT id, name, email, profile_image FROM admins WHERE id = ?');
$adminStmt->execute([$_SESSION['admin_id']]);
$admin = $adminStmt->fetch();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/admin.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1 class="h5 mb-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="text-muted small">Welcome back, <?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?></div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="text-end">
          <div class="fw-semibold small"><?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?></div>
          <div class="text-muted small"><?php echo htmlspecialchars($admin['email'] ?? ''); ?></div>
        </div>
        <img
          src="<?php echo !empty($admin['profile_image']) ? 'uploads/' . htmlspecialchars($admin['profile_image']) : 'https://via.placeholder.com/40'; ?>"
          alt="Profile"
          class="rounded-circle"
          width="40"
          height="40"
        />
      </div>
    </div>
    <div class="admin-content">
