<?php
require __DIR__ . "/bootstrap.php";
require_admin();

$result = $conn->query("SELECT o.id, o.staff_code, o.name, o.designation, o.department, o.phone, o.status,
                               u.id AS user_id, u.username, u.status AS user_status
                        FROM operators o
                        LEFT JOIN users u ON u.operator_id = o.id
                        ORDER BY o.staff_code, o.name");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = $row['user_id'] === null ? null : (int)$row['user_id'];
    $row['usage_count'] = staff_usage_count($conn, $row['id']);
    $row['has_active_work'] = staff_has_active_work($conn, $row['id']);
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
