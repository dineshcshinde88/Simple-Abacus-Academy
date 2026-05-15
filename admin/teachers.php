<?php
$pageTitle = 'Teachers';
$activeMenu = 'teachers';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

function admin_teachers_env_value(string $path, string $key): string
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

function admin_teachers_database_name(PDO $pdo): string
{
    return (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
}

function admin_teachers_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function admin_teachers_backend_pdo(PDO $adminPdo): PDO
{
    $databaseUrl = admin_teachers_env_value(__DIR__ . '/../backend/.env', 'DATABASE_URL');
    if ($databaseUrl === '') {
        $databaseUrl = admin_teachers_env_value(__DIR__ . '/../.env', 'DATABASE_URL');
    }

    $parts = $databaseUrl !== '' ? parse_url($databaseUrl) : false;
    if (!is_array($parts)) {
        return $adminPdo;
    }

    $host = (string) ($parts['host'] ?? 'localhost');
    $port = (string) ($parts['port'] ?? '3306');
    $db = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';
    $user = isset($parts['user']) ? urldecode((string) $parts['user']) : '';
    $pass = isset($parts['pass']) ? urldecode((string) $parts['pass']) : '';

    if ($db === '' || $user === '' || $db === admin_teachers_database_name($adminPdo)) {
        return $adminPdo;
    }

    return new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function admin_teacher_course_label(?string $courseType): string
{
    return match ((string) $courseType) {
        'abacus' => 'Abacus',
        'vedic_maths' => 'Vedic Maths',
        default => 'Abacus',
    };
}

function admin_teacher_profile_picture_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
    if ($path !== '' && str_starts_with($path, '/uploads/')) {
        $fileName = basename($path);
        if (is_file(__DIR__ . '/../backend/uploads/' . $fileName)) {
            return '../backend/uploads/' . rawurlencode($fileName);
        }
    }

    return $url;
}

function admin_teachers_sync_approved_instructors(PDO $teacherPdo, PDO $instructorPdo): void
{
    $experienceSelect = admin_teachers_column_exists($instructorPdo, 'instructors', 'experience') ? 'experience' : "'' AS experience";
    $stmt = $instructorPdo->query(
        "SELECT full_name, email, mobile, course_type, qualification, {$experienceSelect}, career_started, students_trained, address, profile_picture, created_at
         FROM instructors
         WHERE status = 'approved' AND is_verified = 1"
    );
    $approvedInstructors = $stmt->fetchAll();
    if (!$approvedInstructors) {
        return;
    }

    $syncStmt = $teacherPdo->prepare(
        'INSERT INTO teachers (name, email, phone, expertise, joining_date, status, qualification, experience, location, specialization, image, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
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

    foreach ($approvedInstructors as $instructor) {
        $careerStarted = trim((string) ($instructor['career_started'] ?? ''));
        $experience = trim((string) ($instructor['experience'] ?? ''));
        $studentsTrained = trim((string) ($instructor['students_trained'] ?? ''));
        $specialization = admin_teacher_course_label($instructor['course_type'] ?? null);
        $syncStmt->execute([
            (string) ($instructor['full_name'] ?? ''),
            (string) ($instructor['email'] ?? ''),
            (string) (($instructor['mobile'] ?? '') ?: 'Not added'),
            $specialization,
            substr((string) (($instructor['created_at'] ?? '') ?: date('Y-m-d')), 0, 10),
            'active',
            (string) (($instructor['qualification'] ?? '') ?: 'Certified Abacus Trainer'),
            $experience !== '' ? $experience : ($careerStarted !== '' ? 'Teaching since ' . $careerStarted : 'Certified Trainer'),
            (string) (($instructor['address'] ?? '') ?: 'Online'),
            $specialization,
            admin_teacher_profile_picture_url($instructor['profile_picture'] ?? ''),
            $studentsTrained !== ''
                ? 'Approved tutor with experience training ' . $studentsTrained . ' students.'
                : 'Approved tutor from instructor registrations.',
        ]);
    }
}

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
    $teacherCount = (int) $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
    if ($teacherCount === 0) {
        $seedStmt = $pdo->prepare(
            'INSERT INTO teachers (name, email, phone, expertise, joining_date, status, qualification, experience, location, specialization, image, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
    }
} catch (Throwable $e) {
    $errors[] = 'Default teacher setup failed: ' . $e->getMessage();
}

try {
    admin_teachers_sync_approved_instructors($pdo, admin_teachers_backend_pdo($pdo));
} catch (Throwable $e) {
    $errors[] = 'Approved instructors could not be synced to teachers: ' . $e->getMessage();
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

try {
    $teacherBackendPdo = admin_teachers_backend_pdo($pdo);
    $experienceSelect = admin_teachers_column_exists($teacherBackendPdo, 'instructors', 'experience') ? 'experience' : "'' AS experience";
    $stmt = $teacherBackendPdo->query(
        "SELECT id, full_name, email, mobile, course_type, qualification, {$experienceSelect}, career_started, students_trained, address, profile_picture, created_at, status
         FROM instructors
         WHERE status = 'approved' AND is_verified = 1
         ORDER BY created_at DESC"
    );
    $approvedInstructors = $stmt->fetchAll();
    $existingTeacherEmails = array_flip(array_map(static fn (array $teacher): string => strtolower((string) ($teacher['email'] ?? '')), $teachers));

    foreach ($approvedInstructors as $instructor) {
        $email = strtolower((string) ($instructor['email'] ?? ''));
        if ($email !== '' && isset($existingTeacherEmails[$email])) {
            continue;
        }

        $careerStarted = trim((string) ($instructor['career_started'] ?? ''));
        $experience = trim((string) ($instructor['experience'] ?? ''));
        $studentsTrained = trim((string) ($instructor['students_trained'] ?? ''));
        $teachers[] = [
            'id' => 'instructor-' . (string) ($instructor['id'] ?? $email),
            'name' => (string) ($instructor['full_name'] ?? ''),
            'email' => (string) ($instructor['email'] ?? ''),
            'phone' => (string) ($instructor['mobile'] ?? ''),
            'expertise' => admin_teacher_course_label($instructor['course_type'] ?? null),
            'qualification' => (string) (($instructor['qualification'] ?? '') ?: 'Certified Abacus Trainer'),
            'experience' => $experience !== '' ? $experience : ($careerStarted !== '' ? 'Teaching since ' . $careerStarted : 'Certified Trainer'),
            'location' => (string) (($instructor['address'] ?? '') ?: 'Online'),
            'specialization' => admin_teacher_course_label($instructor['course_type'] ?? null),
            'image' => admin_teacher_profile_picture_url($instructor['profile_picture'] ?? ''),
            'description' => $studentsTrained !== ''
                ? 'Approved tutor with experience training ' . $studentsTrained . ' students.'
                : 'Approved tutor from instructor registrations.',
            'joining_date' => substr((string) ($instructor['created_at'] ?? ''), 0, 10),
            'status' => 'active',
            'source' => 'approved_instructor',
        ];
    }
} catch (Throwable $e) {
    $errors[] = 'Approved instructors could not be added to teacher list: ' . $e->getMessage();
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
                  <td>
                    <div><?php echo htmlspecialchars($teacher['name']); ?></div>
                    <?php if (($teacher['source'] ?? '') === 'approved_instructor'): ?>
                      <div class="text-muted small">Approved instructor</div>
                    <?php endif; ?>
                  </td>
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
                    <img
                      src="<?php echo htmlspecialchars(admin_image_url_or_placeholder($teacher['image'] ?? '', (string) $teacher['name'])); ?>"
                      alt="<?php echo htmlspecialchars($teacher['name']); ?>"
                      width="48"
                      height="48"
                      class="rounded object-fit-cover border"
                      onerror="<?php echo admin_image_fallback_attr((string) $teacher['name']); ?>"
                    />
                  </td>
                  <td><?php echo htmlspecialchars($teacher['joining_date']); ?></td>
                  <td>
                    <span class="badge bg-<?php echo $teacher['status'] === 'active' ? 'success' : 'secondary'; ?>">
                      <?php echo htmlspecialchars($teacher['status']); ?>
                    </span>
                  </td>
                  <td class="d-flex gap-2">
                    <?php if (($teacher['source'] ?? '') === 'approved_instructor'): ?>
                      <a class="btn btn-sm btn-outline-secondary" href="instructors.php">Manage</a>
                    <?php else: ?>
                      <a class="btn btn-sm btn-outline-primary" href="teachers.php?edit=<?php echo (int) $teacher['id']; ?>">Edit</a>
                      <a class="btn btn-sm btn-outline-danger" href="teachers.php?delete=<?php echo (int) $teacher['id']; ?>" onclick="return confirm('Delete this teacher?');">Delete</a>
                    <?php endif; ?>
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
