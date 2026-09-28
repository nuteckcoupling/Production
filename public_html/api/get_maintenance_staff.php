<?php
require __DIR__ . '/bootstrap.php';
require_auth();
$result = $conn->query("SELECT o.id, o.staff_code, o.name, o.designation,
        CASE WHEN u.id IS NOT NULL AND u.status = 'Active' AND u.role = 'Maintenance' THEN 1 ELSE 0 END AS has_maintenance_login
    FROM operators o
    LEFT JOIN users u ON u.operator_id = o.id
    WHERE o.status = 'Active' AND LOWER(TRIM(o.department)) = 'maintenance'
    ORDER BY o.name");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['has_maintenance_login'] = (bool)$row['has_maintenance_login'];
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
