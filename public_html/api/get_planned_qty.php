<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$machine_id = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : 0;
$part_id = isset($_GET['part_id']) ? (int)$_GET['part_id'] : 0;

if ($machine_id <= 0 || $part_id <= 0) {
    http_response_code(400);
    json_response(["error" => "Machine Number and Part Name are required"]);
    exit;
}

json_response(["planned_qty" => get_planned_qty($conn, $machine_id, $part_id)]);
$conn->close();
?>
