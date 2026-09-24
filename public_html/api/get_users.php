<?php
require __DIR__ . "/bootstrap.php";
$current = require_admin();

$result = $conn->query("SELECT u.id, u.username, u.role, u.operator_id, u.status, u.last_login,
                               u.last_failed_login, u.failed_login_attempts, u.locked_until,
                               u.must_change_password, u.created_at,
                               o.staff_code, o.name AS operator_name, o.status AS operator_status
                        FROM users u
                        LEFT JOIN operators o ON o.id = u.operator_id
                        ORDER BY FIELD(u.role, 'Admin', 'Operator/Supervisor'), u.username");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['operator_id'] = $row['operator_id'] === null ? null : (int)$row['operator_id'];
    $row['is_current'] = $row['id'] === (int)$current['id'];
    $row['failed_login_attempts'] = (int)$row['failed_login_attempts'];
    $row['must_change_password'] = (bool)$row['must_change_password'];
    $row['is_locked'] = $row['locked_until'] !== null && strtotime($row['locked_until']) > time();
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
