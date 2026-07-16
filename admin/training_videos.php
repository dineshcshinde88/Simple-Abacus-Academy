<?php
$pageTitle = 'Training Video Management';
$activeMenu = 'training_videos';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/instructor_training_helpers.php';

function tv_uuid(): string {
  $data = random_bytes(16);
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function tv_ensure_schema(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS instructor_training_videos (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    program VARCHAR(40) NOT NULL,
    level VARCHAR(80) NOT NULL,
    sequence_number INT NOT NULL DEFAULT 1,
    cloudinary_public_id VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(500) NULL,
    duration_seconds INT NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_itv_library (program, level, sequence_number),
    INDEX idx_itv_status (status)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  $pdo->exec("CREATE TABLE IF NOT EXISTS instructor_video_subscriptions (
    id CHAR(36) PRIMARY KEY,
    instructor_id CHAR(36) NOT NULL,
    plan_name VARCHAR(80) NOT NULL DEFAULT '90 Days',
    duration_days INT NOT NULL DEFAULT 90,
    payment_method VARCHAR(40) NULL,
    payment_amount DECIMAL(10,2) NULL,
    payment_reference VARCHAR(255) NULL,
    payment_note TEXT NULL,
    start_date DATETIME NOT NULL,
    expiry_date DATETIME NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    activated_by_admin_id VARCHAR(64) NULL,
    activated_at DATETIME NULL,
    admin_note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_ivs_instructor_status (instructor_id, status),
    INDEX idx_ivs_expiry (expiry_date)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");  $pdo->exec("CREATE TABLE IF NOT EXISTS instructor_video_progress (
    id CHAR(36) PRIMARY KEY,
    instructor_id CHAR(36) NOT NULL,
    video_id CHAR(36) NOT NULL,
    subscription_id CHAR(36) NULL,
    current_position_seconds INT NOT NULL DEFAULT 0,
    maximum_watched_position_seconds INT NOT NULL DEFAULT 0,
    unique_watched_seconds INT NOT NULL DEFAULT 0,
    duration_seconds INT NOT NULL DEFAULT 0,
    completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    last_watched_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_ivp_instructor_video (instructor_id, video_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

$success = '';
$errors = [];
$editing = null;
$videoPdo = $pdo;

try {
  admin_training_ensure_instructor_schema($pdo);
  tv_ensure_schema($pdo);
  $videoPdo = admin_training_backend_pdo($pdo);
  admin_training_ensure_instructor_schema($videoPdo);
  tv_ensure_schema($videoPdo);
} catch (Throwable $e) {
  $errors[] = 'Training video setup failed: ' . $e->getMessage();
}

if (isset($_GET['edit'])) {
  $stmt = $videoPdo->prepare('SELECT * FROM instructor_training_videos WHERE id = ? LIMIT 1');
  $stmt->execute([(string) $_GET['edit']]);
  $editing = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string) ($_POST['action'] ?? '');
  try {
    if ($action === 'save') {
      $id = (string) ($_POST['id'] ?? '');
      $payload = [
        trim((string) $_POST['title']),
        trim((string) ($_POST['description'] ?? '')),
        (string) $_POST['program'],
        (string) $_POST['level'],
        max(1, (int) $_POST['sequence_number']),
        trim((string) $_POST['cloudinary_public_id']),
        trim((string) ($_POST['thumbnail'] ?? '')),
        max(0, (int) $_POST['duration_seconds']),
        (string) $_POST['status'],
      ];
      if ($payload[0] === '' || $payload[5] === '') {
        throw new RuntimeException('Title and Cloudinary public ID are required.');
      }
      if ($id !== '') {
        $stmt = $videoPdo->prepare('UPDATE instructor_training_videos SET title=?, description=?, program=?, level=?, sequence_number=?, cloudinary_public_id=?, thumbnail=?, duration_seconds=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?');
        $stmt->execute([...$payload, $id]);
        $success = 'Video updated.';
      } else {
        $stmt = $videoPdo->prepare('INSERT INTO instructor_training_videos (id, title, description, program, level, sequence_number, cloudinary_public_id, thumbnail, duration_seconds, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
        $stmt->execute([tv_uuid(), ...$payload]);
        $success = 'Video added.';
      }
    } elseif ($action === 'delete') {
      $videoPdo->prepare("UPDATE instructor_training_videos SET status = 'unpublished', updated_at = UTC_TIMESTAMP() WHERE id = ?")->execute([(string) $_POST['id']]);
      $success = 'Video unpublished.';
    }
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }
}

$videos = $videoPdo->query("SELECT v.*, COUNT(p.id) AS completed_count
  FROM instructor_training_videos v
  LEFT JOIN instructor_video_progress p ON p.video_id = v.id AND p.is_completed = 1
  GROUP BY v.id
  ORDER BY v.program, v.level, v.sequence_number")->fetchAll();

$reports = $videoPdo->query("SELECT i.full_name, i.mobile, i.email,
    COALESCE(s.status, 'none') AS subscription_status,
    s.start_date, s.expiry_date,
    COUNT(p.id) AS touched_videos,
    SUM(CASE WHEN p.is_completed = 1 THEN 1 ELSE 0 END) AS completed_videos,
    ROUND(AVG(COALESCE(p.completion_percentage, 0)), 2) AS overall_completion,
    MAX(p.last_watched_at) AS last_watched_at
  FROM instructors i
  LEFT JOIN instructor_video_subscriptions s ON s.instructor_id = i.id
  LEFT JOIN instructor_video_progress p ON p.instructor_id = i.id
  WHERE i.status = 'approved'
  GROUP BY i.id, s.id
  ORDER BY last_watched_at DESC, i.full_name
  LIMIT 100")->fetchAll();

$abacusLevels = ['Foundation','Level 1','Level 2','Level 3','Level 4','Level 5','Level 6','Level 7'];
$vedicLevels = ['Level 1','Level 2','Level 3','Level 4'];
?>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title"><?php echo $editing ? 'Edit Video' : 'Add Cloudinary Video'; ?></h5>
        <p class="text-muted small">Upload videos in Cloudinary, then paste the protected public ID here. Video files are not stored on this hosting server.</p>
        <form method="post">
          <input type="hidden" name="action" value="save" />
          <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($editing['id'] ?? '')); ?>" />
          <label class="form-label">Title</label>
          <input class="form-control" name="title" value="<?php echo htmlspecialchars((string) ($editing['title'] ?? '')); ?>" required />
          <label class="form-label mt-3">Description</label>
          <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars((string) ($editing['description'] ?? '')); ?></textarea>
          <div class="row g-3 mt-1">
            <div class="col-6">
              <label class="form-label">Program</label>
              <select class="form-select" name="program">
                <option value="abacus" <?php echo (($editing['program'] ?? '') === 'abacus') ? 'selected' : ''; ?>>Abacus</option>
                <option value="vedic_maths" <?php echo (($editing['program'] ?? '') === 'vedic_maths') ? 'selected' : ''; ?>>Vedic Maths</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Level</label>
              <select class="form-select" name="level">
                <?php foreach (array_unique([...$abacusLevels, ...$vedicLevels]) as $level): ?>
                  <option <?php echo (($editing['level'] ?? '') === $level) ? 'selected' : ''; ?>><?php echo htmlspecialchars($level); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Sequence</label>
              <input class="form-control" name="sequence_number" type="number" min="1" value="<?php echo htmlspecialchars((string) ($editing['sequence_number'] ?? '1')); ?>" />
            </div>
            <div class="col-6">
              <label class="form-label">Duration seconds</label>
              <input class="form-control" name="duration_seconds" type="number" min="0" value="<?php echo htmlspecialchars((string) ($editing['duration_seconds'] ?? '0')); ?>" />
            </div>
          </div>
          <label class="form-label mt-3">Cloudinary public ID</label>
          <input class="form-control" name="cloudinary_public_id" value="<?php echo htmlspecialchars((string) ($editing['cloudinary_public_id'] ?? '')); ?>" required />
          <label class="form-label mt-3">Thumbnail URL</label>
          <input class="form-control" name="thumbnail" value="<?php echo htmlspecialchars((string) ($editing['thumbnail'] ?? '')); ?>" />
          <label class="form-label mt-3">Status</label>
          <select class="form-select" name="status">
            <option value="published" <?php echo (($editing['status'] ?? '') !== 'unpublished') ? 'selected' : ''; ?>>Published</option>
            <option value="unpublished" <?php echo (($editing['status'] ?? '') === 'unpublished') ? 'selected' : ''; ?>>Unpublished</option>
          </select>
          <button class="btn btn-primary mt-3" type="submit"><?php echo $editing ? 'Update Video' : 'Add Video'; ?></button>
          <?php if ($editing): ?><a class="btn btn-link mt-3" href="training_videos.php">Cancel</a><?php endif; ?>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Video Library</h5>
        <div class="table-responsive mt-3">
          <table class="table align-middle">
            <thead><tr><th>Video</th><th>Program</th><th>Cloudinary</th><th>Status</th><th>Completed</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($videos as $video): ?>
                <tr>
                  <td><div><?php echo htmlspecialchars($video['sequence_number'] . '. ' . $video['title']); ?></div><div class="text-muted small"><?php echo htmlspecialchars((string) $video['description']); ?></div></td>
                  <td><?php echo htmlspecialchars($video['program'] === 'vedic_maths' ? 'Vedic Maths' : 'Abacus'); ?><div class="text-muted small"><?php echo htmlspecialchars($video['level']); ?></div></td>
                  <td><code><?php echo htmlspecialchars($video['cloudinary_public_id']); ?></code><div class="text-muted small"><?php echo (int) $video['duration_seconds']; ?> sec</div></td>
                  <td><span class="badge bg-secondary"><?php echo htmlspecialchars($video['status']); ?></span></td>
                  <td><?php echo (int) $video['completed_count']; ?></td>
                  <td class="d-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="?edit=<?php echo htmlspecialchars($video['id']); ?>">Edit</a><form method="post"><input type="hidden" name="action" value="delete" /><input type="hidden" name="id" value="<?php echo htmlspecialchars($video['id']); ?>" /><button class="btn btn-sm btn-outline-warning">Unpublish</button></form></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$videos): ?><tr><td colspan="6" class="text-center text-muted">No videos added.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-body">
        <h5 class="card-title">Instructor Progress Report</h5>
        <div class="table-responsive mt-3">
          <table class="table align-middle">
            <thead><tr><th>Instructor</th><th>Subscription</th><th>Overall</th><th>Completed</th><th>Last Watched</th></tr></thead>
            <tbody>
              <?php foreach ($reports as $report): ?>
                <tr>
                  <td><div><?php echo htmlspecialchars($report['full_name']); ?></div><div class="text-muted small"><?php echo htmlspecialchars($report['mobile'] . ' ' . $report['email']); ?></div></td>
                  <td><?php echo htmlspecialchars($report['subscription_status']); ?><div class="text-muted small"><?php echo htmlspecialchars((string) $report['start_date']); ?> to <?php echo htmlspecialchars((string) $report['expiry_date']); ?></div></td>
                  <td><?php echo htmlspecialchars((string) ($report['overall_completion'] ?? '0')); ?>%</td>
                  <td><?php echo (int) $report['completed_videos']; ?></td>
                  <td><?php echo htmlspecialchars((string) ($report['last_watched_at'] ?? '-')); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$reports): ?><tr><td colspan="5" class="text-center text-muted">No progress yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>





