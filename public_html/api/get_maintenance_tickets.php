<?php
require __DIR__ . '/bootstrap.php';
$user = require_auth();
$status = trim((string)($_GET['status'] ?? 'open'));
$where = $status === 'all' ? '' : "WHERE mt.status <> 'Closed'";
$result = $conn->query("SELECT mt.*, m.code AS machine_code, m.name AS machine_name,
        reporter.username AS reported_by, assigned.name AS assigned_staff_name, assigned.staff_code AS assigned_staff_code,
        TIMESTAMPDIFF(MINUTE, mt.breakdown_started_at, COALESCE(mt.machine_running_confirmed_at, NOW())) AS downtime_minutes,
        CASE WHEN mt.work_started_at IS NULL THEN NULL
             ELSE TIMESTAMPDIFF(MINUTE, mt.work_started_at, COALESCE(mt.repair_completed_at, NOW())) END AS repair_minutes
    FROM maintenance_tickets mt
    JOIN machines m ON m.id = mt.machine_id
    JOIN users reporter ON reporter.id = mt.reported_by_user_id
    LEFT JOIN operators assigned ON assigned.id = mt.assigned_staff_id
    $where
    ORDER BY (mt.status = 'Closed'), mt.breakdown_started_at DESC
    LIMIT 200");
$rows = [];
while ($row = $result->fetch_assoc()) {
    foreach (['id','machine_id','job_id','assigned_staff_id','downtime_minutes','repair_minutes'] as $field) {
        $row[$field] = $row[$field] === null ? null : (int)$row[$field];
    }
    $is_maintenance = ($user['role'] ?? '') === 'Maintenance';
    $is_assigned = (int)($user['operator_id'] ?? 0) === (int)($row['assigned_staff_id'] ?? 0);
    $row['can_assign'] = $is_maintenance && in_array($row['status'], ['Open','Assigned'], true);
    $row['can_start_work'] = $is_maintenance && in_array($row['status'], ['Open','Assigned'], true)
        && ($row['assigned_staff_id'] === null || $is_assigned);
    $row['can_complete_repair'] = $is_maintenance && $row['status'] === 'In Progress' && $is_assigned;
    $row['can_confirm_running'] = ($user['role'] ?? '') === 'Operator/Supervisor' && $row['status'] === 'Repair Completed';
    $rows[] = $row;
}

$summary = $conn->query("SELECT
    SUM(status <> 'Closed') AS open_count,
    SUM(status = 'In Progress') AS in_progress_count,
    SUM(status = 'Repair Completed') AS awaiting_test_count,
    COALESCE(ROUND(AVG(CASE WHEN status = 'Closed' THEN TIMESTAMPDIFF(MINUTE, work_started_at, repair_completed_at) END)), 0) AS mttr_minutes
    FROM maintenance_tickets")->fetch_assoc();
json_response(['tickets' => $rows, 'summary' => [
    'open_count' => (int)($summary['open_count'] ?? 0),
    'in_progress_count' => (int)($summary['in_progress_count'] ?? 0),
    'awaiting_test_count' => (int)($summary['awaiting_test_count'] ?? 0),
    'mttr_minutes' => (int)($summary['mttr_minutes'] ?? 0)
]]);
$conn->close();
?>
