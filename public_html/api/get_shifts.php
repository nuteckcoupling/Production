<?php
require __DIR__ . "/bootstrap.php";
$include_all = ($_GET['all'] ?? '') === '1';
if ($include_all) {
    require_admin();
} else {
    require_auth();
}

$sql = "SELECT id, code, name, TIME_FORMAT(start_time, '%H:%i') AS start_time,
               TIME_FORMAT(end_time, '%H:%i') AS end_time, shift_hours, status
        FROM shifts";
if (!$include_all) {
    $sql .= " WHERE status = 'Active'";
}
$sql .= " ORDER BY start_time, code";
$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['shift_hours'] = (float)$row['shift_hours'];
    $row['usage_count'] = $include_all ? shift_usage_count($conn, $row['code']) : 0;
    $row['label'] = $row['name'] . ' — ' . $row['start_time'] . ' to ' . $row['end_time'];
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
