<?php
$pageTitle = 'Blog';
$activeMenu = 'blogs';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

function admin_blog_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?: '';
    return trim($slug, '-') ?: 'blog';
}

function admin_blog_unique_slug(PDO $pdo, string $table, string $slug, int $ignoreId = 0): string
{
    $base = admin_blog_slug($slug);
    $candidate = $base;
    $suffix = 2;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$candidate];
        if ($ignoreId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $base . '-' . $suffix;
        $suffix += 1;
    }
}

function admin_blog_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS blog_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(160) NOT NULL,
            slug VARCHAR(180) NOT NULL UNIQUE,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS blogs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NULL,
            title VARCHAR(220) NOT NULL,
            slug VARCHAR(240) NOT NULL UNIQUE,
            author VARCHAR(140) NOT NULL DEFAULT 'Admin',
            publish_date DATE NULL,
            excerpt TEXT NULL,
            description MEDIUMTEXT NULL,
            featured_image VARCHAR(255) NULL,
            meta_title VARCHAR(220) NULL,
            meta_description TEXT NULL,
            meta_keywords VARCHAR(255) NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_blogs_category (category_id),
            INDEX idx_blogs_status (status),
            CONSTRAINT fk_blogs_category FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function admin_blog_seed_previous_posts(PDO $pdo): void
{
    $category = $pdo->prepare('SELECT id FROM blog_categories WHERE slug = ? LIMIT 1');
    $category->execute(['educational']);
    $categoryId = (int) $category->fetchColumn();
    if ($categoryId <= 0) {
        $stmt = $pdo->prepare('INSERT INTO blog_categories (category_name, slug, status) VALUES (?, ?, ?)');
        $stmt->execute(['Educational', 'educational', 'active']);
        $categoryId = (int) $pdo->lastInsertId();
    }

    $posts = [
        [
            'slug' => 'benefits-of-teaching-kids-abacus',
            'title' => '10 Real Benefits of Teaching Kids How to Use an Abacus',
            'date' => '2026-03-02',
            'excerpt' => "How abacus training boosts your child's brain power and builds focus and memory.",
            'image' => 'IMG20260221125635.jpg',
            'description' => '<h2>Why Abacus Still Matters</h2><p>Abacus learning helps children visualize numbers and build strong number sense.</p><p>It improves attention span, memory, and mental calculation speed over time.</p><h2>Top Benefits Parents Notice</h2><p>Better focus during homework and class activities.</p><p>Faster recall and confidence in solving arithmetic problems.</p><p>Improved hand eye coordination and mental agility.</p>',
        ],
        [
            'slug' => 'best-digital-abacus-for-kids',
            'title' => 'Best Digital Abacus for Kids, Students and Teachers',
            'date' => '2026-02-19',
            'excerpt' => 'Best digital abacus for kids, students and teachers for online classes and practice.',
            'image' => 'IMG20260221144800.jpg',
            'description' => '<h2>Choosing the Right Digital Abacus</h2><p>Look for a simple interface, clear bead visuals, and guided practice mode.</p><p>Tools that combine worksheets, video lessons, and drills are most effective.</p><h2>Who Benefits the Most</h2><p>Students practicing at home need easy access and progress tracking.</p><p>Teachers need downloadable worksheets and class-ready exercises.</p>',
        ],
        [
            'slug' => 'level-wise-worksheets-for-beginners',
            'title' => 'Level-Wise Abacus Worksheets for Beginners',
            'date' => '2026-02-06',
            'excerpt' => 'Abacus education is not just about learning mathematics or doing sums quickly.',
            'image' => 'IMG20260221144855.jpg',
            'description' => '<h2>Start With The Basics</h2><p>Beginner worksheets focus on number recognition and simple addition.</p><p>Structured levels help children advance at a steady pace.</p><h2>Build Consistency</h2><p>Short daily practice sessions create lasting improvements.</p><p>Printable worksheets keep practice focused and measurable.</p>',
        ],
        [
            'slug' => 'what-is-a-digital-abacus-frame',
            'title' => 'What Is a Digital Abacus Frame? A Complete Guide',
            'date' => '2026-01-27',
            'excerpt' => 'Learning maths is changing fast. Yes, the abacus has been part of it too.',
            'image' => 'IMG20260222115937.jpg',
            'description' => '<h2>Digital Meets Traditional</h2><p>A digital abacus frame simulates physical beads with guided practice.</p><p>It enables remote learning, progress tracking, and instant feedback.</p><h2>How It Helps Learners</h2><p>Students stay engaged with interactive drills and step-by-step prompts.</p><p>Parents can track practice time and accuracy trends easily.</p>',
        ],
    ];

    $sourceDir = dirname(__DIR__) . '/src/assets/photos';
    $targetDir = __DIR__ . '/uploads/blogs';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    foreach ($posts as $post) {
        $exists = $pdo->prepare('SELECT id FROM blogs WHERE slug = ? LIMIT 1');
        $exists->execute([$post['slug']]);
        if ($exists->fetch()) {
            continue;
        }

        $targetName = 'legacy-' . $post['image'];
        $source = $sourceDir . '/' . $post['image'];
        $target = $targetDir . '/' . $targetName;
        if (is_file($source) && !is_file($target)) {
            copy($source, $target);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO blogs (category_id, title, slug, author, publish_date, excerpt, description, featured_image, meta_title, meta_description, meta_keywords, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $categoryId,
            $post['title'],
            $post['slug'],
            'Admin',
            $post['date'],
            $post['excerpt'],
            $post['description'],
            'uploads/blogs/' . $targetName,
            $post['title'],
            $post['excerpt'],
            'abacus, vedic maths, kids maths, simple abacus',
            'published',
        ]);
    }
}

function admin_blog_upload(string $field, ?string $current = null): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $current;
    }
    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Featured image upload failed.');
    }
    $tmp = (string) ($_FILES[$field]['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid image upload.');
    }
    $info = @getimagesize($tmp);
    if (!$info) {
        throw new RuntimeException('Please upload a valid image file.');
    }
    $ext = match ((string) ($info['mime'] ?? '')) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => '',
    };
    if ($ext === '') {
        throw new RuntimeException('Allowed image formats: JPG, PNG, WEBP, GIF.');
    }
    $dir = __DIR__ . '/uploads/blogs';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = 'blog_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        throw new RuntimeException('Could not save uploaded image.');
    }
    return 'uploads/blogs/' . $name;
}

admin_blog_schema($pdo);
admin_blog_seed_previous_posts($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'add_category' || $action === 'edit_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['category_name'] ?? ''));
            $status = in_array(($_POST['status'] ?? 'active'), ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            if ($name === '') {
                $errors[] = 'Category name is required.';
            } else {
                $slug = admin_blog_unique_slug($pdo, 'blog_categories', $name, $action === 'edit_category' ? $id : 0);
                if ($action === 'add_category') {
                    $stmt = $pdo->prepare('INSERT INTO blog_categories (category_name, slug, status) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $slug, $status]);
                    $success = 'Category added successfully.';
                } else {
                    $stmt = $pdo->prepare('UPDATE blog_categories SET category_name = ?, slug = ?, status = ? WHERE id = ?');
                    $stmt->execute([$name, $slug, $status, $id]);
                    $success = 'Category updated successfully.';
                }
            }
        }

        if ($action === 'delete_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM blog_categories WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Category deleted successfully.';
        }

        if ($action === 'delete_blog') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM blogs WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Blog deleted successfully.';
        }

        if ($action === 'toggle_blog') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = ($_POST['status'] ?? '') === 'published' ? 'draft' : 'published';
            $stmt = $pdo->prepare('UPDATE blogs SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            $success = 'Blog status updated.';
        }

        if ($action === 'add_blog' || $action === 'edit_blog') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $author = trim((string) ($_POST['author'] ?? 'Admin'));
            $publishDate = trim((string) ($_POST['publish_date'] ?? date('Y-m-d')));
            $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $metaTitle = trim((string) ($_POST['meta_title'] ?? ''));
            $metaDescription = trim((string) ($_POST['meta_description'] ?? ''));
            $metaKeywords = trim((string) ($_POST['meta_keywords'] ?? ''));
            $status = in_array(($_POST['status'] ?? 'draft'), ['draft', 'published'], true) ? $_POST['status'] : 'draft';
            $currentImage = trim((string) ($_POST['current_featured_image'] ?? ''));

            if ($title === '' || $categoryId <= 0) {
                $errors[] = 'Blog title and category are required.';
            } else {
                $slug = admin_blog_unique_slug($pdo, 'blogs', $title, $action === 'edit_blog' ? $id : 0);
                $image = admin_blog_upload('featured_image', $currentImage !== '' ? $currentImage : null);
                if ($action === 'add_blog') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO blogs (category_id, title, slug, author, publish_date, excerpt, description, featured_image, meta_title, meta_description, meta_keywords, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$categoryId, $title, $slug, $author ?: 'Admin', $publishDate ?: null, $excerpt, $description, $image, $metaTitle, $metaDescription, $metaKeywords, $status]);
                    $success = 'Blog added successfully.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE blogs SET category_id = ?, title = ?, slug = ?, author = ?, publish_date = ?, excerpt = ?, description = ?, featured_image = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ? WHERE id = ?'
                    );
                    $stmt->execute([$categoryId, $title, $slug, $author ?: 'Admin', $publishDate ?: null, $excerpt, $description, $image, $metaTitle, $metaDescription, $metaKeywords, $status, $id]);
                    $success = 'Blog updated successfully.';
                }
            }
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$categoryEdit = null;
if (isset($_GET['edit_category'])) {
    $stmt = $pdo->prepare('SELECT * FROM blog_categories WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_category']]);
    $categoryEdit = $stmt->fetch();
}

$blogEdit = null;
if (isset($_GET['edit_blog'])) {
    $stmt = $pdo->prepare('SELECT * FROM blogs WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_blog']]);
    $blogEdit = $stmt->fetch();
}

$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY created_at DESC')->fetchAll();
$activeCategories = $pdo->query("SELECT * FROM blog_categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();

$search = trim((string) ($_GET['search'] ?? ''));
$categoryFilter = (int) ($_GET['category'] ?? 0);
$statusFilter = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(b.title LIKE ? OR b.author LIKE ? OR b.excerpt LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($categoryFilter > 0) {
    $where[] = 'b.category_id = ?';
    $params[] = $categoryFilter;
}
if (in_array($statusFilter, ['draft', 'published'], true)) {
    $where[] = 'b.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs b {$whereSql}");
$countStmt->execute($params);
$totalBlogs = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalBlogs / $limit);

$blogStmt = $pdo->prepare(
    "SELECT b.*, c.category_name
     FROM blogs b
     LEFT JOIN blog_categories c ON c.id = b.category_id
     {$whereSql}
     ORDER BY b.created_at DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$blogStmt->execute($params);
$blogs = $blogStmt->fetchAll();
?>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?php echo htmlspecialchars($err); ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-xl-4">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title"><?php echo $categoryEdit ? 'Edit Category' : 'Add Category'; ?></h5>
        <form method="post" class="mt-3">
          <input type="hidden" name="action" value="<?php echo $categoryEdit ? 'edit_category' : 'add_category'; ?>" />
          <?php if ($categoryEdit): ?><input type="hidden" name="id" value="<?php echo (int) $categoryEdit['id']; ?>" /><?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input class="form-control" name="category_name" value="<?php echo htmlspecialchars($categoryEdit['category_name'] ?? ''); ?>" required />
            <div class="form-text">Slug is generated automatically.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="active" <?php echo (($categoryEdit['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo (($categoryEdit['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
          <button class="btn btn-primary w-100"><?php echo $categoryEdit ? 'Update Category' : 'Add Category'; ?></button>
          <?php if ($categoryEdit): ?><a href="blogs.php" class="btn btn-link w-100">Cancel Edit</a><?php endif; ?>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Category List</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($categories as $category): ?>
                <tr>
                  <td><div class="fw-semibold"><?php echo htmlspecialchars($category['category_name']); ?></div><div class="small text-muted"><?php echo htmlspecialchars($category['slug']); ?></div></td>
                  <td><span class="badge bg-<?php echo $category['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($category['status']); ?></span></td>
                  <td class="small"><?php echo htmlspecialchars(date('d M Y', strtotime((string) $category['created_at']))); ?></td>
                  <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="blogs.php?edit_category=<?php echo (int) $category['id']; ?>">Edit</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this category? Blogs in this category will become uncategorized.');">
                      <input type="hidden" name="action" value="delete_category" />
                      <input type="hidden" name="id" value="<?php echo (int) $category['id']; ?>" />
                      <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$categories): ?><tr><td colspan="4" class="text-muted text-center">No categories yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title"><?php echo $blogEdit ? 'Edit Blog' : 'Add Blog'; ?></h5>
        <form method="post" enctype="multipart/form-data" class="mt-3">
          <input type="hidden" name="action" value="<?php echo $blogEdit ? 'edit_blog' : 'add_blog'; ?>" />
          <?php if ($blogEdit): ?>
            <input type="hidden" name="id" value="<?php echo (int) $blogEdit['id']; ?>" />
            <input type="hidden" name="current_featured_image" value="<?php echo htmlspecialchars($blogEdit['featured_image'] ?? ''); ?>" />
          <?php endif; ?>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Blog Title</label>
              <input class="form-control" name="title" value="<?php echo htmlspecialchars($blogEdit['title'] ?? ''); ?>" required />
            </div>
            <div class="col-md-4">
              <label class="form-label">Blog Category</label>
              <select class="form-select" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($activeCategories as $category): ?>
                  <option value="<?php echo (int) $category['id']; ?>" <?php echo ((int) ($blogEdit['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['category_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Author Name</label>
              <input class="form-control" name="author" value="<?php echo htmlspecialchars($blogEdit['author'] ?? $adminName); ?>" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Blog Date</label>
              <input type="date" class="form-control" name="publish_date" value="<?php echo htmlspecialchars($blogEdit['publish_date'] ?? date('Y-m-d')); ?>" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="draft" <?php echo (($blogEdit['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo (($blogEdit['status'] ?? '') === 'published') ? 'selected' : ''; ?>>Published</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Short Description / Excerpt</label>
              <textarea class="form-control" name="excerpt" rows="2"><?php echo htmlspecialchars($blogEdit['excerpt'] ?? ''); ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Full Blog Description</label>
              <textarea class="form-control rich-editor" name="description" rows="10"><?php echo htmlspecialchars($blogEdit['description'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Featured Image Upload</label>
              <input type="file" class="form-control" name="featured_image" accept="image/*" id="featured-image-input" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Image Preview</label>
              <div class="border rounded p-2 bg-light" style="min-height: 96px;">
                <img id="featured-image-preview" src="<?php echo htmlspecialchars(admin_asset_url($blogEdit['featured_image'] ?? '')); ?>" alt="" style="max-height: 120px; max-width: 100%; <?php echo empty($blogEdit['featured_image']) ? 'display:none;' : ''; ?>" />
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Meta Title</label>
              <input class="form-control" name="meta_title" value="<?php echo htmlspecialchars($blogEdit['meta_title'] ?? ''); ?>" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Meta Keywords</label>
              <input class="form-control" name="meta_keywords" value="<?php echo htmlspecialchars($blogEdit['meta_keywords'] ?? ''); ?>" />
            </div>
            <div class="col-12">
              <label class="form-label">Meta Description</label>
              <textarea class="form-control" name="meta_description" rows="2"><?php echo htmlspecialchars($blogEdit['meta_description'] ?? ''); ?></textarea>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary"><?php echo $blogEdit ? 'Update Blog' : 'Add Blog'; ?></button>
            <?php if ($blogEdit): ?><a href="blogs.php" class="btn btn-outline-secondary">Cancel Edit</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <h5 class="card-title mb-0">Blog List</h5>
          <form class="d-flex flex-wrap gap-2" method="get">
            <input class="form-control" style="max-width: 220px;" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search blogs" />
            <select class="form-select" style="max-width: 180px;" name="category">
              <option value="0">All Categories</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo (int) $category['id']; ?>" <?php echo $categoryFilter === (int) $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['category_name']); ?></option>
              <?php endforeach; ?>
            </select>
            <select class="form-select" style="max-width: 150px;" name="status">
              <option value="">All Status</option>
              <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
              <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
            </select>
            <button class="btn btn-outline-primary">Filter</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Image</th><th>Blog Title</th><th>Category</th><th>Author</th><th>Publish Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($blogs as $blog): ?>
                <tr>
                  <td><img src="<?php echo htmlspecialchars(admin_image_url_or_placeholder($blog['featured_image'] ?? '', $blog['title'])); ?>" alt="" style="width:72px;height:52px;object-fit:cover;border-radius:6px;" /></td>
                  <td><div class="fw-semibold"><?php echo htmlspecialchars($blog['title']); ?></div><div class="small text-muted">/blogs/<?php echo htmlspecialchars($blog['slug']); ?></div></td>
                  <td><?php echo htmlspecialchars($blog['category_name'] ?? 'Uncategorized'); ?></td>
                  <td><?php echo htmlspecialchars($blog['author']); ?></td>
                  <td><?php echo htmlspecialchars($blog['publish_date'] ? date('d M Y', strtotime((string) $blog['publish_date'])) : '-'); ?></td>
                  <td><span class="badge bg-<?php echo $blog['status'] === 'published' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($blog['status']); ?></span></td>
                  <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="../blogs/<?php echo htmlspecialchars($blog['slug']); ?>">View</a>
                    <a class="btn btn-sm btn-outline-primary" href="blogs.php?edit_blog=<?php echo (int) $blog['id']; ?>">Edit</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="toggle_blog" />
                      <input type="hidden" name="id" value="<?php echo (int) $blog['id']; ?>" />
                      <input type="hidden" name="status" value="<?php echo htmlspecialchars($blog['status']); ?>" />
                      <button class="btn btn-sm btn-outline-warning"><?php echo $blog['status'] === 'published' ? 'Unpublish' : 'Publish'; ?></button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this blog?');">
                      <input type="hidden" name="action" value="delete_blog" />
                      <input type="hidden" name="id" value="<?php echo (int) $blog['id']; ?>" />
                      <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$blogs): ?><tr><td colspan="7" class="text-center text-muted">No blogs found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($totalPages > 1): ?>
          <nav><ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
          </ul></nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
  if (window.ClassicEditor) {
    document.querySelectorAll('.rich-editor').forEach(function (element) {
      ClassicEditor.create(element).catch(function (error) {
        console.error(error);
      });
    });
  }
  const imageInput = document.getElementById('featured-image-input');
  const imagePreview = document.getElementById('featured-image-preview');
  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function () {
      const file = this.files && this.files[0];
      if (!file) return;
      imagePreview.src = URL.createObjectURL(file);
      imagePreview.style.display = 'block';
    });
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
