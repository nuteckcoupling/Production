<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$result = $conn->query("SELECT id, cutter_num, cutter_type FROM cutters WHERE status = 'Active' AND deleted_at IS NULL ORDER BY cutter_num");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
