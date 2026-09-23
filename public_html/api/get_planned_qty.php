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

$stmt = $conn->prepare("SELECT COALESCE(MAX(total_qty), 0) AS planned_qty
                        FROM daily_entries
                        WHERE machine_id = ? AND part_id = ? AND shift_hours = 12");
$stmt->bind_param("ii", $machine_id, $part_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

json_response(["planned_qty" => (int)$row['planned_qty']]);

$stmt->close();
$conn->close();
?>
