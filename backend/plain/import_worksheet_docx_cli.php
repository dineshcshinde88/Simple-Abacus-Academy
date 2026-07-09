<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';
require_once __DIR__ . '/controllers_subscriptions.php';
require_once __DIR__ . '/controllers_worksheet_sub.php';
require_once __DIR__ . '/worksheet_docx_importer.php';

load_env_file(__DIR__ . '/../.env');

$force = false;
$files = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--force') {
        $force = true;
        continue;
    }
    $files[] = $arg;
}

if (!$files) {
    $files = [
        'C:\\Users\\Dell\\Downloads\\SIMPLE ABACUS practice paper - Level 0 (1).docx',
        'C:\\Users\\Dell\\Downloads\\SIMPLE ABACUS practice paper - Level 1 (1).docx',
    ];
}

foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "File not found: {$file}\n");
        exit(1);
    }
}

try {
    echo json_encode(worksheet_import_docx_files($files, $force), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
