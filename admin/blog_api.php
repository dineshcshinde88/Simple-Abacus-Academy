<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/image.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function blog_api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function blog_api_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function blog_api_image_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return $scheme . '://' . $host . $base . '/' . ltrim(admin_asset_url($path), '/');
}

if (!blog_api_table_exists($pdo, 'blog_categories') || !blog_api_table_exists($pdo, 'blogs')) {
    blog_api_json(['categories' => [], 'blogs' => [], 'total' => 0, 'page' => 1, 'totalPages' => 0]);
}

$action = $_GET['action'] ?? 'list';

if ($action === 'categories') {
    $rows = $pdo->query("SELECT id, category_name, slug FROM blog_categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();
    blog_api_json(['categories' => $rows]);
}

if ($action === 'detail') {
    $slug = trim((string) ($_GET['slug'] ?? ''));
    if ($slug === '') {
        blog_api_json(['message' => 'Blog not found'], 404);
    }
    $stmt = $pdo->prepare(
        "SELECT b.*, c.category_name, c.slug AS category_slug
         FROM blogs b
         LEFT JOIN blog_categories c ON c.id = b.category_id
         WHERE b.slug = ? AND b.status = 'published'
         LIMIT 1"
    );
    $stmt->execute([$slug]);
    $blog = $stmt->fetch();
    if (!$blog) {
        blog_api_json(['message' => 'Blog not found'], 404);
    }
    $blog['featured_image_url'] = blog_api_image_url($blog['featured_image'] ?? '');
    blog_api_json(['blog' => $blog]);
}

$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = min(24, max(1, (int) ($_GET['limit'] ?? 9)));
$offset = ($page - 1) * $limit;

$where = ["b.status = 'published'"];
$params = [];
if ($search !== '') {
    $where[] = '(b.title LIKE ? OR b.excerpt LIKE ? OR b.description LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($category !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $category;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs b LEFT JOIN blog_categories c ON c.id = b.category_id {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $limit);

$stmt = $pdo->prepare(
    "SELECT b.id, b.category_id, b.title, b.slug, b.author, b.publish_date, b.excerpt, b.featured_image,
            b.meta_title, b.meta_description, b.meta_keywords, c.category_name, c.slug AS category_slug
     FROM blogs b
     LEFT JOIN blog_categories c ON c.id = b.category_id
     {$whereSql}
     ORDER BY b.publish_date DESC, b.created_at DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$stmt->execute($params);
$blogs = $stmt->fetchAll();
foreach ($blogs as &$blog) {
    $blog['featured_image_url'] = blog_api_image_url($blog['featured_image'] ?? '');
}
unset($blog);

$categories = $pdo->query("SELECT id, category_name, slug FROM blog_categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();
blog_api_json(['blogs' => $blogs, 'categories' => $categories, 'total' => $total, 'page' => $page, 'totalPages' => $totalPages]);
