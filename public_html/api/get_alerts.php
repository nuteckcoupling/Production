<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$settings = system_settings_values($conn);
$handover_overdue_minutes = $settings['handover_overdue_minutes'];
$idle_alert_minutes = $settings['idle_alert_minutes'];
$shift_end_grace_minutes = $settings['shift_end_grace_minutes'];
$alerts = [];

$shiftSql = "SELECT j.id AS job_id, m.id AS machine_id, m.code AS machine_code, m.name AS machine_name,
        o.name AS operator_name, s.shift, s.shift_hours, s.started_at,
        TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) AS elapsed_minutes
    FROM job_shifts s
    JOIN production_jobs j ON j.id = s.job_id AND j.status = 'Running'
    JOIN machines m ON m.id = j.machine_id
    JOIN operators o ON o.id = s.operator_id
    WHERE s.status = 'Running'
      AND TIMESTAMPDIFF(MINUTE, s.started_at, NOW()) >= (s.shift_hours * 60) + ?";
$shiftStmt = $conn->prepare($shiftSql);
$shiftStmt->bind_param('i', $shift_end_grace_minutes);
$shiftStmt->execute();
$result = $shiftStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $overdue = max(0, (int)$row['elapsed_minutes'] - (int)round((float)$row['shift_hours'] * 60));
    $alerts[] = [
        'type' => 'shift_end_pending',
        'severity' => 'critical',
        'title' => 'Shift End Pending',
        'message' => $row['operator_name'] . "'s shift exceeded its scheduled time by " . $overdue . ' min.',
        'machine_id' => (int)$row['machine_id'],
        'machine_code' => $row['machine_code'],
        'machine_name' => $row['machine_name'],
        'job_id' => (int)$row['job_id'],
        'status' => 'Running',
        'elapsed_minutes' => (int)$row['elapsed_minutes'],
        'started_at' => $row['started_at']
    ];
}
$shiftStmt->close();

$handoverStmt = $conn->prepare("SELECT j.id AS job_id, m.id AS machine_id, m.code AS machine_code,
        m.name AS machine_name, o.name AS operator_name, j.status_changed_at,
        TIMESTAMPDIFF(MINUTE, j.status_changed_at, NOW()) AS pending_minutes
    FROM production_jobs j
    JOIN machines m ON m.id = j.machine_id
    LEFT JOIN operators o ON o.id = j.current_operator_id
    WHERE j.status = 'Handover Pending'
    ORDER BY j.status_changed_at");
$handoverStmt->execute();
$result = $handoverStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending = max(0, (int)$row['pending_minutes']);
    $alerts[] = [
        'type' => 'handover_pending',
        'severity' => $pending >= $handover_overdue_minutes ? 'critical' : 'warning',
        'title' => 'Handover Not Accepted',
        'message' => 'Waiting for the next operator for ' . $pending . ' min.',
        'machine_id' => (int)$row['machine_id'],
        'machine_code' => $row['machine_code'],
        'machine_name' => $row['machine_name'],
        'job_id' => (int)$row['job_id'],
        'status' => 'Handover Pending',
        'elapsed_minutes' => $pending,
        'started_at' => $row['status_changed_at']
    ];
}
$handoverStmt->close();

$idleStmt = $conn->prepare("SELECT m.id AS machine_id, m.code AS machine_code, m.name AS machine_name,
        last_job.last_activity,
        TIMESTAMPDIFF(MINUTE, last_job.last_activity, NOW()) AS idle_minutes
    FROM machines m
    LEFT JOIN production_jobs active_job
      ON active_job.machine_id = m.id
     AND active_job.status IN ('Running', 'Handover Pending', 'Breakdown', 'Stopped')
    JOIN (
        SELECT machine_id, MAX(COALESCE(completed_at, status_changed_at)) AS last_activity
        FROM production_jobs
        WHERE status = 'Completed'
        GROUP BY machine_id
    ) last_job ON last_job.machine_id = m.id
    WHERE m.status = 'Active'
      AND active_job.id IS NULL
      AND TIMESTAMPDIFF(MINUTE, last_job.last_activity, NOW()) >= ?
    ORDER BY last_job.last_activity");
$idleStmt->bind_param("i", $idle_alert_minutes);
$idleStmt->execute();
$result = $idleStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $idle = max(0, (int)$row['idle_minutes']);
    $alerts[] = [
        'type' => 'machine_idle',
        'severity' => 'warning',
        'title' => 'Machine Idle',
        'message' => 'No active job for ' . $idle . ' min.',
        'machine_id' => (int)$row['machine_id'],
        'machine_code' => $row['machine_code'],
        'machine_name' => $row['machine_name'],
        'job_id' => null,
        'status' => 'Available',
        'elapsed_minutes' => $idle,
        'started_at' => $row['last_activity']
    ];
}
$idleStmt->close();

usort($alerts, function ($a, $b) {
    $rank = ['critical' => 0, 'warning' => 1];
    $severity = ($rank[$a['severity']] ?? 9) <=> ($rank[$b['severity']] ?? 9);
    return $severity !== 0 ? $severity : $b['elapsed_minutes'] <=> $a['elapsed_minutes'];
});

json_response([
    'alerts' => $alerts,
    'counts' => [
        'total' => count($alerts),
        'critical' => count(array_filter($alerts, fn($alert) => $alert['severity'] === 'critical')),
        'warning' => count(array_filter($alerts, fn($alert) => $alert['severity'] === 'warning'))
    ],
    'thresholds' => [
        'handover_overdue_minutes' => $handover_overdue_minutes,
        'idle_alert_minutes' => $idle_alert_minutes,
        'shift_end_grace_minutes' => $shift_end_grace_minutes
    ]
]);
$conn->close();
?>
