<?php
$pageTitle = 'Teachers';
$activeMenu = 'teachers';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            expertise VARCHAR(160) NOT NULL,
            qualification VARCHAR(160) NOT NULL DEFAULT 'Certified Abacus Trainer',
            experience VARCHAR(120) NOT NULL DEFAULT '',
            location VARCHAR(160) NOT NULL DEFAULT '',
            specialization VARCHAR(120) NOT NULL DEFAULT 'Abacus',
            image VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT NULL,
            joining_date DATE NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $teacherColumns = [
        'qualification' => "VARCHAR(160) NOT NULL DEFAULT 'Certified Abacus Trainer'",
        'experience' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'location' => "VARCHAR(160) NOT NULL DEFAULT ''",
        'specialization' => "VARCHAR(120) NOT NULL DEFAULT 'Abacus'",
        'image' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'description' => "TEXT NULL",
    ];

    foreach ($teacherColumns as $column => $definition) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute(['teachers', $column]);

        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE teachers ADD COLUMN {$column} {$definition}");
        }
    }
} catch (Throwable $e) {
    $errors[] = 'Teachers table setup failed: ' . $e->getMessage();
}

$defaultTeachers = [
    [
        'name' => 'Poonam Yuvraj Gavhane',
        'email' => 'poonam.gavhane@simpleabacus.com',
        'phone' => 'Not added',
        'expertise' => 'Abacus',
        'joining_date' => '2026-01-01',
        'status' => 'active',
        'qualification' => 'Certified Abacus Trainer',
        'experience' => '5+ Years Experience',
        'location' => 'Pune, Maharashtra',
        'specialization' => 'Abacus',
        'image' => '/assets/teachers/poonam-gavhane.png',
        'description' => 'Patient, structured instruction that builds number sense, focus, and confident mental math habits.',
    ],
    [
        'name' => 'Mahanthi Kamini Devi',
        'email' => 'mahanthi.kamini.devi@simpleabacus.com',
        'phone' => 'Not added',
        'expertise' => 'Abacus',
        'joining_date' => '2026-01-01',
        'status' => 'active',
        'qualification' => 'Certified Abacus Trainer',
        'experience' => '6+ Years Experience',
        'location' => 'Thane, Maharashtra',
        'specialization' => 'Abacus',
        'image' => '/assets/teachers/mahanthi-kamini-devi.png',
        'description' => 'Known for engaging classes and step-by-step guidance that keeps learners motivated and consistent.',
    ],
    [
        'name' => 'Nayana Uday Patil',
        'email' => 'nayana.uday.patil@simpleabacus.com',
        'phone' => 'Not added',
        'expertise' => 'Abacus',
        'joining_date' => '2026-01-01',
        'status' => 'active',
        'qualification' => 'Certified Abacus Trainer',
        'experience' => '4+ Years Experience',
        'location' => 'Pune, Maharashtra',
        'specialization' => 'Abacus',
        'image' => '/assets/teachers/nayana-uday-patil.png',
        'description' => 'Focuses on accuracy, speed, and confidence with child-friendly teaching and regular feedback.',
    ],
    [
        'name' => 'Ashvini Balu Talekar',
        'email' => 'ashvini.balu.talekar@simpleabacus.com',
        'phone' => 'Not added',
        'expertise' => 'Abacus',
        'joining_date' => '2026-01-01',
        'status' => 'active',
        'qualification' => 'Certified Abacus Trainer',
        'experience' => '5+ Years Experience',
        'location' => 'Pune, Maharashtra',
        'specialization' => 'Abacus',
        'image' => '/assets/teachers/ashvini-balu-talekar.png',
        'description' => 'Encouraging mentor who blends fun practice with clear fundamentals and personalized attention.',
    ],
];

try {
    $seedStmt = $pdo->prepare(
        'INSERT INTO teachers (name, email, phone, expertise, joining_date, status, qualification, experience, location, specialization, image, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           phone = VALUES(phone),
           expertise = VALUES(expertise),
           qualification = VALUES(qualification),
           experience = VALUES(experience),
           location = VALUES(location),
           specialization = VALUES(specialization),
           image = VALUES(image),
           description = VALUES(description),
           status = VALUES(status)'
    );
    foreach ($defaultTeachers as $teacher) {
        $seedStmt->execute([
            $teacher['name'],
            $teacher['email'],
            $teacher['phone'],
            $teacher['expertise'],
            $teacher['joining_date'],
            $teacher['status'],
            $teacher['qualification'],
            $teacher['experience'],
            $teacher['location'],
            $teacher['specialization'],
            $teacher['image'],
            $teacher['description'],
        ]);
    }
} catch (Throwable $e) {
    $errors[] = 'Default teacher setup failed: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $joiningDate = $_POST['joining_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $qualification = trim($_POST['qualification'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($action === 'add' || $action === 'edit') {
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $expertise === '' || $joiningDate === '' || $qualification === '' || $experience === '' || $location === '' || $specialization === '' || $image === '' || $description === '') {
            $errors[] = 'Please fill all required fields.';
        }
    }

    if (!$errors && $action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO teachers (name, email, phone, expertise, joining_date, status, qualification, experience, location, specialization, image, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $expertise, $joiningDate, $status, $qualification, $experience, $location, $specialization, $image, $description]);
        $success = 'Teacher added successfully.';
    }

    if (!$errors && $action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE teachers SET name = ?, email = ?, phone = ?, expertise = ?, joining_date = ?, status = ?, qualification = ?, experience = ?, location = ?, specialization = ?, image = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $email, $phone, $expertise, $joiningDate, $status, $qualification, $experience, $location, $specialization, $image, $description, $id]);
        $success = 'Teacher updated successfully.';
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM teachers WHERE id = ?');
    $stmt->execute([$id]);
    $success = 'Teacher deleted successfully.';
}

$teachers = [];
try {
    $teachers = $pdo->query('SELECT * FROM teachers ORDER BY joining_date DESC, id DESC')->fetchAll();
} catch (Throwable $e) {
    $errors[] = 'Teacher list could not be loaded: ' . $e->getMessage();
}

$editTeacher = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([$editId]);
    $editTeacher = $stmt->fetch();
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
  <div class="col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title"><?php echo $editTeacher ? 'Edit Teacher' : 'Add Teacher'; ?></h5>
        <form method="post">
          <input type="hidden" name="action" value="<?php echo $editTeacher ? 'edit' : 'add'; ?>" />
          <?php if ($editTeacher): ?>
            <input type="hidden" name="id" value="<?php echo (int) $editTeacher['id']; ?>" />
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editTeacher['name'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editTeacher['email'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editTeacher['phone'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Expertise</label>
            <input type="text" name="expertise" class="form-control" value="<?php echo htmlspecialchars($editTeacher['expertise'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Qualification</label>
            <input type="text" name="qualification" class="form-control" value="<?php echo htmlspecialchars($editTeacher['qualification'] ?? 'Certified Abacus Trainer'); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Experience</label>
            <input type="text" name="experience" class="form-control" value="<?php echo htmlspecialchars($editTeacher['experience'] ?? ''); ?>" placeholder="5+ Years Experience" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($editTeacher['location'] ?? ''); ?>" placeholder="Pune, Maharashtra" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Specialization Badge</label>
            <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($editTeacher['specialization'] ?? 'Abacus'); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Image Path</label>
            <input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($editTeacher['image'] ?? ''); ?>" placeholder="/assets/teachers/name.png" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($editTeacher['description'] ?? ''); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Joining Date</label>
            <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($editTeacher['joining_date'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="active" <?php echo (($editTeacher['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo (($editTeacher['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
          <button class="btn btn-primary w-100" type="submit"><?php echo $editTeacher ? 'Update Teacher' : 'Add Teacher'; ?></button>
          <?php if ($editTeacher): ?>
            <a href="teachers.php" class="btn btn-link w-100">Cancel Edit</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Teacher List</h5>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Profile</th>
                <th>Image</th>
                <th>Joining Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($teachers as $teacher): ?>
                <tr>
                  <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                  <td>
                    <div><?php echo htmlspecialchars($teacher['email']); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars($teacher['qualification']); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($teacher['experience']); ?> · <?php echo htmlspecialchars($teacher['location']); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($teacher['specialization']); ?></div>
                  </td>
                  <td>
                    <?php if (!empty($teacher['image'])): ?>
                      <img src="<?php echo htmlspecialchars(admin_asset_url((string) $teacher['image'])); ?>" alt="<?php echo htmlspecialchars($teacher['name']); ?>" width="48" height="48" class="rounded object-fit-cover border" />
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($teacher['joining_date']); ?></td>
                  <td>
                    <span class="badge bg-<?php echo $teacher['status'] === 'active' ? 'success' : 'secondary'; ?>">
                      <?php echo htmlspecialchars($teacher['status']); ?>
                    </span>
                  </td>
                  <td class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="teachers.php?edit=<?php echo (int) $teacher['id']; ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-danger" href="teachers.php?delete=<?php echo (int) $teacher['id']; ?>" onclick="return confirm('Delete this teacher?');">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$teachers): ?>
                <tr><td colspan="6" class="text-center text-muted">No teachers found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
