<?php
require __DIR__ . "/bootstrap.php";
$user = require_admin();
$filename = trim($_GET['file'] ?? '');
try {
    $path = backup_file_path($filename);
    if (!is_file($path)) json_error('Backup file not found', 404);
    audit_log($conn, $user, 'Backup Management', 'Downloaded',
        'Downloaded database backup ' . $filename, 'backup', null);
    $conn->close();
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
} catch (RuntimeException $error) {
    json_error($error->getMessage(), in_array($error->getCode(), [400, 404], true) ? $error->getCode() : 500);
}
?>
