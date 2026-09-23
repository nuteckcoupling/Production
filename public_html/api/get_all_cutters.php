<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$trash = ($_GET['trash'] ?? '') === '1';
$where = $trash ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';
$result = $conn->query("SELECT id, cutter_num, cutter_type, lead_angle, rpm_stroke, status, remarks, deleted_at
    FROM cutters WHERE $where ORDER BY cutter_num");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
