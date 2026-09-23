<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$week_start = trim($_GET['week_start'] ?? '');
$date = DateTime::createFromFormat('Y-m-d', $week_start);
if ($week_start !== '' && (!$date || $date->format('Y-m-d') !== $week_start)) {
    json_error('Invalid week date', 400);
}

$sql = "SELECT wp.id, wp.week_start, wp.week_end, wp.machine_id, m.code AS machine_code,
               m.name AS machine_name, wp.shift, wp.part_id, p.part_name, wp.operation,
               wp.planned_qty, wp.priority, wp.remarks, wp.status, wp.created_at,
               COALESCE(o.name, u.username) AS prepared_by
        FROM weekly_plans wp
        JOIN machines m ON m.id = wp.machine_id
        JOIN parts p ON p.id = wp.part_id
        JOIN users u ON u.id = wp.created_by_user_id
        LEFT JOIN operators o ON o.id = u.operator_id";
if ($week_start !== '') {
    $sql .= " WHERE wp.week_start = ?";
}
$sql .= " ORDER BY wp.week_start DESC, m.code, wp.shift, p.part_name, wp.operation";

if ($week_start !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $week_start);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['machine_id'] = (int)$row['machine_id'];
    $row['part_id'] = (int)$row['part_id'];
    $row['planned_qty'] = (int)$row['planned_qty'];
    $rows[] = $row;
}
json_response($rows);
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
