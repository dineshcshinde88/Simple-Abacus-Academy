<?php

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function envv(string $key, $default = null)
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

function get_all_headers_safe(): array
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        return is_array($headers) ? $headers : [];
    }

    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_')) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = $value;
        }
    }

    return $headers;
}

function request_header(string $name): ?string
{
    $target = strtolower($name);
    foreach (get_all_headers_safe() as $k => $v) {
        if (strtolower((string) $k) === $target) {
            return is_array($v) ? implode(',', $v) : (string) $v;
        }
    }
    return null;
}

function host_matches_pattern(string $host, string $pattern): bool
{
    $host = strtolower($host);
    $pattern = strtolower($pattern);

    if ($pattern === '') {
        return false;
    }

    if (str_starts_with($pattern, '*.')) {
        $suffix = substr($pattern, 1); // keep leading dot
        return str_ends_with($host, $suffix) && strlen($host) > strlen($suffix);
    }

    return $host === $pattern;
}

function is_origin_allowed(string $origin, array $allowed): bool
{
    if (in_array('*', $allowed, true)) {
        return true;
    }

    $parts = parse_url($origin);
    if (!is_array($parts)) {
        return false;
    }

    $originHost = strtolower((string) ($parts['host'] ?? ''));
    $originScheme = strtolower((string) ($parts['scheme'] ?? ''));
    $originPort = isset($parts['port']) ? (string) $parts['port'] : '';
    if ($originHost === '' || $originScheme === '') {
        return false;
    }

    foreach ($allowed as $entryRaw) {
        $entry = trim($entryRaw);
        if ($entry === '') {
            continue;
        }

        if ($entry === $origin) {
            return true;
        }

        // Support wildcard host patterns like https://*.simpleabacus.com
        if (preg_match('#^(https?)://(\*\.[^/:]+)(?::([0-9]+))?$#i', $entry, $m) === 1) {
            $scheme = strtolower($m[1]);
            $hostPattern = strtolower($m[2]);
            $port = isset($m[3]) ? (string) $m[3] : '';
            if ($scheme === $originScheme && $port === $originPort && host_matches_pattern($originHost, $hostPattern)) {
                return true;
            }
        }
    }

    return false;
}

function apply_cors_headers(): void
{
    $origin = request_header('Origin');
    $allowed = array_values(array_filter(array_map('trim', explode(',', (string) envv('CORS_ORIGIN', '')))));

    if (!empty($allowed) && $origin !== null && is_origin_allowed($origin, $allowed)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } elseif (empty($allowed)) {
        header('Access-Control-Allow-Origin: *');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, payment-signature, stripe-signature');
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function now_sql(): string
{
    return gmdate('Y-m-d H:i:s');
}

function uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function get_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host;
}

function normalize_request_path(string $path): string
{
    $path = $path !== '' ? $path : '/';
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($scriptDir !== '' && $scriptDir !== '.' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
        $path = substr($path, strlen($scriptDir)) ?: '/';
    }

    if (str_starts_with($path, '/index.php')) {
        $path = substr($path, strlen('/index.php')) ?: '/';
    }

    return $path === '' ? '/' : $path;
}

function request_body_data(string $method): array
{
    if ($method === 'GET') {
        return [];
    }

    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    parse_str((string) $raw, $parsed);
    return is_array($parsed) ? $parsed : [];
}

function db_conn(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) envv('DB_HOST', '127.0.0.1');
    $port = (string) envv('DB_PORT', '3306');
    $db = (string) envv('DB_DATABASE', '');
    $user = (string) envv('DB_USERNAME', '');
    $pass = (string) envv('DB_PASSWORD', '');

    if ($db === '' && envv('DATABASE_URL', '') !== '') {
        $parts = parse_url((string) envv('DATABASE_URL', ''));
        if (is_array($parts)) {
            $host = (string) ($parts['host'] ?? $host);
            $port = isset($parts['port']) ? (string) $parts['port'] : $port;
            $db = isset($parts['path']) ? ltrim((string) $parts['path'], '/') : $db;
            $user = (string) ($parts['user'] ?? $user);
            $pass = (string) ($parts['pass'] ?? $pass);
        }
    }

    if ($db === '' || $user === '') {
        json_response(['message' => 'Database configuration is missing in .env'], 500);
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        json_response(['message' => 'Database connection failed'], 500);
    }

    return $pdo;
}

function db_one(string $sql, array $params = []): ?array
{
    $stmt = db_conn()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_all(string $sql, array $params = []): array
{
    $stmt = db_conn()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function db_value(string $sql, array $params = [])
{
    $stmt = db_conn()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function db_exec_sql(string $sql, array $params = []): void
{
    $stmt = db_conn()->prepare($sql);
    $stmt->execute($params);
}

function handle_upload_file(string $field): string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return '';
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }

    $tmp = (string) ($_FILES[$field]['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return '';
    }

    $name = (string) ($_FILES[$field]['name'] ?? 'file');
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $safeExt = $ext !== '' ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '';
    $finalName = uuid_v4() . $safeExt;

    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    $target = $uploadDir . '/' . $finalName;
    if (!move_uploaded_file($tmp, $target)) {
        return '';
    }

    $baseUrl = rtrim((string) envv('BASE_URL', get_base_url()), '/');
    return $baseUrl . '/uploads/' . rawurlencode($finalName);
}
