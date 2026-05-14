<?php

function admin_asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    if (strpos($path, '/assets/') === 0) {
        return $path;
    }

    if (strpos($path, 'assets/') === 0) {
        return '/' . $path;
    }

    if (strpos($path, 'uploads/') === 0) {
        return $path;
    }

    return 'uploads/' . ltrim($path, '/');
}

function admin_local_image_exists(string $path): bool
{
    $path = trim($path);
    if ($path === '' || preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, 'data:')) {
        return $path !== '';
    }

    $rootDir = dirname(__DIR__, 2);
    $adminDir = dirname(__DIR__);
    $candidates = [];

    if (strpos($path, '/assets/') === 0) {
        $candidates[] = $rootDir . '/public' . $path;
        $candidates[] = $rootDir . $path;
    } elseif (strpos($path, 'assets/') === 0) {
        $candidates[] = $adminDir . '/' . $path;
        $candidates[] = $rootDir . '/public/' . $path;
        $candidates[] = $rootDir . '/' . $path;
    } elseif (strpos($path, 'uploads/') === 0) {
        $candidates[] = $adminDir . '/' . $path;
    } else {
        $candidates[] = $adminDir . '/uploads/' . ltrim($path, '/');
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return true;
        }
    }

    return false;
}

function admin_placeholder_avatar(string $name = 'Profile'): string
{
    $initials = strtoupper(substr(trim($name) !== '' ? trim($name) : 'A', 0, 1));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">'
        . '<rect width="80" height="80" rx="40" fill="#4b1e83"/>'
        . '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" '
        . 'font-family="Arial, sans-serif" font-size="34" font-weight="700" fill="#ffffff">'
        . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8')
        . '</text></svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function admin_image_url_or_placeholder(?string $path, string $name = 'Profile'): string
{
    $path = trim((string) $path);
    if ($path === '' || !admin_local_image_exists($path)) {
        return admin_placeholder_avatar($name);
    }

    return admin_asset_url($path);
}

function admin_image_fallback_attr(string $name = 'Profile'): string
{
    $fallback = admin_placeholder_avatar($name);
    return "this.onerror=null;this.src='" . htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8') . "';";
}
