<?php
require "config.php";
require "auth.php";
require_auth();

$part_id = isset($_GET['part_id']) ? (int)$_GET['part_id'] : 0;
if ($part_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid part"]);
    exit;
}

$stmt = $conn->prepare("SELECT component_name FROM part_components WHERE part_id = ? ORDER BY sort_order, component_name");
$stmt->bind_param("i", $part_id);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$stmt->close();
$conn->close();
?>
