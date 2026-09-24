<?php
require __DIR__ . "/bootstrap.php";
require_auth();
json_response(system_settings_values($conn));
$conn->close();
?>
