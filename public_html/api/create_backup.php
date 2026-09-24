<?php
require __DIR__ . "/bootstrap.php";
$user = require_admin();
try {
    $backup = create_database_backup($conn, 'backup');
    audit_log($conn, $user, 'Backup Management', 'Created',
        'Created database backup ' . $backup['filename'], 'backup', null);
    json_response(['success' => true, 'backup' => $backup], 201);
} catch (Throwable $error) {
    json_error('Unable to create backup: ' . $error->getMessage(), 500);
}
$conn->close();
?>
