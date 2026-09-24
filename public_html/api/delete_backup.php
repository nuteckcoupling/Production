<?php
require __DIR__ . "/bootstrap.php";
$user = require_admin();
$data = json_input();
$filename = trim($data['file'] ?? '');
try {
    $path = backup_file_path($filename);
    if (!is_file($path)) json_error('Backup file not found', 404);
    if (!unlink($path)) throw new RuntimeException('Unable to delete backup file');
    audit_log($conn, $user, 'Backup Management', 'Deleted',
        'Deleted database backup ' . $filename, 'backup', null);
    json_response(['success' => true]);
} catch (RuntimeException $error) {
    json_error($error->getMessage(), in_array($error->getCode(), [400, 404], true) ? $error->getCode() : 500);
}
$conn->close();
?>
