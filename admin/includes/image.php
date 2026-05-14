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
