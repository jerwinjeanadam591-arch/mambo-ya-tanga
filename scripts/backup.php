<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$destination = $argv[1] ?? (dirname(DB_PATH) . '/backups/mambo-' . date('Ymd-His') . '.sqlite');
$destination = is_string($destination) ? $destination : '';
if ($destination === '' || str_contains($destination, "\0")) {
    fwrite(STDERR, "Usage: php scripts/backup.php [destination.sqlite]\n");
    exit(2);
}
$directory = dirname($destination);
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    throw new RuntimeException('Unable to create backup directory.');
}
$destination = realpath($directory) . DIRECTORY_SEPARATOR . basename($destination);
if (file_exists($destination)) {
    throw new RuntimeException('Refusing to overwrite an existing backup.');
}

$pdo = db();
$quoted = $pdo->quote($destination);
$pdo->exec("VACUUM INTO $quoted");
fwrite(STDOUT, "Backup created: $destination\n");
