<?php
require __DIR__ . "/bootstrap.php";
$user = require_admin();
try {
    $backup = create_database_backup($conn, 'backup');
    audit_log($conn, $user, 'Backup Management', 'Created',
        'Created database backup ' . $backup['filename'], 'backup', null);
    json_response(['success' => true, 'backup' => $backup], 201);
} catch (Throwable $error) {
    json_server_error('Create database backup', $error, 'Unable to create database backup');
}
$conn->close();
?>
