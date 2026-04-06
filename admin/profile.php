<?php
$pageTitle = 'Profile';
$activeMenu = 'profile';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid name and email.';
        }

        $profileImage = $admin['profile_image'] ?? null;
        if (!empty($_FILES['profile_image']['name'])) {
            $file = $_FILES['profile_image'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    $errors[] = 'Profile image must be jpg, png, or webp.';
                } else {
                    $newName = 'admin_' . time() . '.' . $ext;
                    $target = __DIR__ . '/uploads/' . $newName;
                    move_uploaded_file($file['tmp_name'], $target);
                    $profileImage = $newName;
                }
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare('UPDATE admins SET name = ?, email = ?, profile_image = ? WHERE id = ?');
            $stmt->execute([$name, $email, $profileImage, $admin['id']]);
            $_SESSION['admin_name'] = $name;
            $success = 'Profile updated successfully.';
            $admin['name'] = $name;
            $admin['email'] = $email;
            $admin['profile_image'] = $profileImage;
        }
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($newPass === '' || $newPass !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $stmt = $pdo->prepare('SELECT password FROM admins WHERE id = ?');
            $stmt->execute([$admin['id']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($current, $row['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
                $stmt->execute([$hash, $admin['id']]);
                $success = 'Password updated successfully.';
            }
        }
    }
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

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Update Profile</h5>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="profile" />
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Profile Image</label>
            <input type="file" name="profile_image" class="form-control" />
          </div>
          <button class="btn btn-primary" type="submit">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Change Password</h5>
        <form method="post">
          <input type="hidden" name="action" value="password" />
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required />
          </div>
          <button class="btn btn-secondary" type="submit">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
