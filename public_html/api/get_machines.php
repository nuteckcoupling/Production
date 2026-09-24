<?php
require __DIR__ . "/bootstrap.php";
$include_all = ($_GET['all'] ?? '') === '1';
if ($include_all) {
    require_admin();
} else {
    require_auth();
}

$sql = "SELECT id, code, name, default_operation, status FROM machines";
if (!$include_all) {
    $sql .= " WHERE status = 'Active'";
}
$sql .= " ORDER BY code";
$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['usage_count'] = $include_all ? machine_usage_count($conn, $row['id']) : 0;
    $row['has_active_job'] = $include_all ? machine_has_active_job($conn, $row['id']) : false;
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
