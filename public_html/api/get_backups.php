<?php
require __DIR__ . "/bootstrap.php";
require_admin();
try {
    json_response(list_database_backups());
} catch (Throwable $error) {
    json_error($error->getMessage(), 500);
}
$conn->close();
?>
