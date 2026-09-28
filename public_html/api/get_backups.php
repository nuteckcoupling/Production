<?php
require __DIR__ . "/bootstrap.php";
require_admin();
try {
    json_response(list_database_backups());
} catch (Throwable $error) {
    json_server_error('List database backups', $error, 'Unable to list database backups');
}
$conn->close();
?>
