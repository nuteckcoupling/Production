<?php
require __DIR__ . "/bootstrap.php";
require_admin();
$systemSettings = system_settings_values($conn);
$analysisHandoverMinutes = (int)$systemSettings['handover_overdue_minutes'];
$analysisShiftGrace = (int)$systemSettings['shift_end_grace_minutes'];

function analysis_period(string $period, string $value): array
{
    $today = new DateTime('today');
    if ($period === 'daily') {
        $expected = $value !== '' ? $value : $today->format('Y-m-d');
        $date = DateTime::createFromFormat('!Y-m-d', $expected);
        if (!$date || $date->format('Y-m-d') !== $expected) json_error('Invalid daily analysis date', 400);
        return [$date, clone $date, $date->format('d M Y')];
    }
    if ($period === 'weekly') {
        $expected = $value !== '' ? $value : $today->format('Y-m-d');
        $date = DateTime::createFromFormat('!Y-m-d', $expected);
        if (!$date || $date->format('Y-m-d') !== $expected) json_error('Invalid weekly analysis date', 400);
        $start = clone $date;
        $start->modify('monday this week');
        $end = clone $start;
        $end->modify('+6 days');
        return [$start, $end, $start->format('d M Y') . ' - ' . $end->format('d M Y')];
    }
    if ($period === 'monthly') {
        $expected = $value !== '' ? $value : $today->format('Y-m');
        $date = DateTime::createFromFormat('!Y-m', $expected);
        if (!$date || $date->format('Y-m') !== $expected) json_error('Invalid monthly analysis month', 400);
        $end = clone $date;
        $end->modify('last day of this month');
        return [$date, $end, $date->format('F Y')];
    }
    json_error('Invalid analysis period', 400);
}

$period = $_GET['period'] ?? 'daily';
$value = trim($_GET['value'] ?? '');
[$start, $end, $label] = analysis_period($period, $value);
$startDate = $start->format('Y-m-d');
$endDate = $end->format('Y-m-d');
$machineId = filter_var($_GET['machine_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$partId = filter_var($_GET['part_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$operation = trim($_GET['operation'] ?? '');
if (strlen($operation) > 150) json_error('Invalid operation filter', 400);

$stmt = $conn->prepare("SELECT
        s.id AS shift_id, s.shift_date, s.shift, s.total_qty, s.ok_qty,
        s.mc_reject_qty, s.rm_defect_qty, s.rework_qty, s.downtime_min,
        s.issue_code, s.remarks AS shift_remarks,
        j.id AS job_id, j.planned_qty, j.status AS job_status, j.component, j.operation,
        m.id AS machine_id, m.code AS machine_code, m.name AS machine_name,
        o.name AS operator_name, p.part_name
    FROM job_shifts s
    JOIN production_jobs j ON j.id = s.job_id
    JOIN machines m ON m.id = j.machine_id
    JOIN operators o ON o.id = s.operator_id
    JOIN parts p ON p.id = j.part_id
    WHERE s.shift_date BETWEEN ? AND ?
      AND (? = 0 OR m.id = ?)
      AND (? = 0 OR p.id = ?)
      AND (? = '' OR j.operation = ?)
    ORDER BY m.code, s.shift_date, s.id");
$stmt->bind_param('ssiiiiss', $startDate, $endDate, $machineId, $machineId,
    $partId, $partId, $operation, $operation);
$stmt->execute();
$result = $stmt->get_result();

$machines = [];
$issues = [];
function &analysis_machine(array &$machines, array $record): array
{
    $id = (int)$record['machine_id'];
    if (!isset($machines[$id])) {
        $machines[$id] = [
            'machine_id' => $id,
            'machine_code' => $record['machine_code'],
            'machine_name' => $record['machine_name'],
            'jobs' => [], 'job_count' => 0, 'shift_count' => 0,
            'planned_qty' => 0, 'total_qty' => 0, 'ok_qty' => 0,
            'mc_reject_qty' => 0, 'rm_defect_qty' => 0, 'rework_qty' => 0,
            'downtime_min' => 0, 'cutter_change_downtime_min' => 0,
            'pending_qty' => 0, 'achievement_percent' => 0, 'reject_percent' => 0,
            'pending_job_count' => 0, 'overdue_job_count' => 0
        ];
    }
    return $machines[$id];
}

while ($record = $result->fetch_assoc()) {
    $machine =& analysis_machine($machines, $record);
    $jobId = (int)$record['job_id'];
    if (!isset($machine['jobs'][$jobId])) {
        $machine['jobs'][$jobId] = true;
        $machine['planned_qty'] += (int)$record['planned_qty'];
        $machine['job_count']++;
    }
    $machine['shift_count']++;
    foreach (['total_qty', 'ok_qty', 'mc_reject_qty', 'rm_defect_qty', 'rework_qty', 'downtime_min'] as $field) {
        $machine[$field] += (int)$record[$field];
    }
    $hasIssue = trim((string)$record['issue_code']) !== '' || trim((string)$record['shift_remarks']) !== ''
        || (int)$record['downtime_min'] > 0 || (int)$record['mc_reject_qty'] > 0
        || (int)$record['rm_defect_qty'] > 0 || (int)$record['rework_qty'] > 0;
    if ($hasIssue) {
        $issues[] = [
            'date' => $record['shift_date'], 'machine_code' => $record['machine_code'],
            'operator_name' => $record['operator_name'], 'part_name' => $record['part_name'],
            'operation' => $record['operation'], 'issue_code' => $record['issue_code'] ?: 'Not specified',
            'downtime_min' => (int)$record['downtime_min'],
            'mc_reject_qty' => (int)$record['mc_reject_qty'],
            'rm_defect_qty' => (int)$record['rm_defect_qty'],
            'rework_qty' => (int)$record['rework_qty'], 'remarks' => $record['shift_remarks']
        ];
    }
    unset($machine);
}
$stmt->close();

// Cutter-change downtime is automatic and is included in analysis separately from manual shift downtime.
$cutterStmt = $conn->prepare("SELECT DATE(cc.completed_at) AS change_date,
        TIMESTAMPDIFF(MINUTE, cc.started_at, cc.completed_at) AS downtime_min,
        cc.reason, cc.remarks, m.id AS machine_id, m.code AS machine_code, m.name AS machine_name,
        p.part_name, j.operation, o.name AS operator_name
    FROM job_cutter_changes cc
    JOIN production_jobs j ON j.id = cc.job_id
    JOIN machines m ON m.id = j.machine_id
    JOIN parts p ON p.id = j.part_id
    LEFT JOIN job_shifts js ON js.id = cc.shift_id
    LEFT JOIN operators o ON o.id = js.operator_id
    WHERE cc.status = 'Completed' AND DATE(cc.completed_at) BETWEEN ? AND ?
      AND (? = 0 OR m.id = ?)
      AND (? = 0 OR p.id = ?)
      AND (? = '' OR j.operation = ?)
    ORDER BY cc.completed_at");
$cutterStmt->bind_param('ssiiiiss', $startDate, $endDate, $machineId, $machineId,
    $partId, $partId, $operation, $operation);
$cutterStmt->execute();
$cutterResult = $cutterStmt->get_result();
while ($record = $cutterResult->fetch_assoc()) {
    $minutes = max(0, (int)$record['downtime_min']);
    $machine =& analysis_machine($machines, $record);
    $machine['cutter_change_downtime_min'] += $minutes;
    $machine['downtime_min'] += $minutes;
    unset($machine);
    $issues[] = [
        'date' => $record['change_date'], 'machine_code' => $record['machine_code'],
        'operator_name' => $record['operator_name'] ?: '-', 'part_name' => $record['part_name'],
        'operation' => $record['operation'], 'issue_code' => 'Cutter Change: ' . $record['reason'],
        'downtime_min' => $minutes, 'mc_reject_qty' => 0, 'rm_defect_qty' => 0,
        'rework_qty' => 0, 'remarks' => $record['remarks']
    ];
}
$cutterStmt->close();

$pendingStmt = $conn->prepare("SELECT j.id AS job_id, j.status, j.planned_qty, j.cumulative_ok_qty,
        GREATEST(j.planned_qty - j.cumulative_ok_qty, 0) AS pending_qty,
        j.operation, j.component, j.started_at, j.status_changed_at, j.remarks,
        m.id AS machine_id, m.code AS machine_code, m.name AS machine_name,
        p.part_name, o.name AS operator_name, COALESCE(sm.name, j.current_shift) AS shift_name,
        js.started_at AS shift_started_at, js.shift_hours,
        CASE WHEN js.id IS NOT NULL THEN DATE_ADD(js.started_at, INTERVAL ROUND(js.shift_hours * 60) MINUTE) ELSE NULL END AS expected_end,
        CASE
          WHEN js.id IS NOT NULL AND NOW() > DATE_ADD(js.started_at, INTERVAL (ROUND(js.shift_hours * 60) + $analysisShiftGrace) MINUTE)
            THEN TIMESTAMPDIFF(MINUTE, DATE_ADD(js.started_at, INTERVAL ROUND(js.shift_hours * 60) MINUTE), NOW())
          WHEN j.status = 'Handover Pending' AND TIMESTAMPDIFF(MINUTE, j.status_changed_at, NOW()) > $analysisHandoverMinutes
            THEN TIMESTAMPDIFF(MINUTE, j.status_changed_at, NOW()) - $analysisHandoverMinutes
          ELSE 0
        END AS overdue_minutes
    FROM production_jobs j
    JOIN machines m ON m.id = j.machine_id
    JOIN parts p ON p.id = j.part_id
    LEFT JOIN operators o ON o.id = j.current_operator_id
    LEFT JOIN shifts sm ON sm.code = j.current_shift
    LEFT JOIN job_shifts js ON js.job_id = j.id AND js.status = 'Running'
    WHERE j.status IN ('Running','Handover Pending','Breakdown','Stopped')
      AND DATE(COALESCE(j.started_at, j.status_changed_at)) <= ?
      AND DATE(COALESCE(j.completed_at, NOW())) >= ?
      AND (? = 0 OR m.id = ?)
      AND (? = 0 OR p.id = ?)
      AND (? = '' OR j.operation = ?)
    ORDER BY overdue_minutes DESC, m.code, j.id");
$pendingStmt->bind_param('ssiiiiss', $endDate, $startDate, $machineId, $machineId,
    $partId, $partId, $operation, $operation);
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
$pendingJobs = [];
while ($job = $pendingResult->fetch_assoc()) {
    foreach (['job_id', 'planned_qty', 'cumulative_ok_qty', 'pending_qty', 'machine_id', 'overdue_minutes'] as $field) {
        $job[$field] = (int)$job[$field];
    }
    $job['shift_hours'] = $job['shift_hours'] === null ? null : (float)$job['shift_hours'];
    $pendingJobs[] = $job;
    $machine =& analysis_machine($machines, $job);
    $machine['pending_job_count']++;
    if ($job['overdue_minutes'] > 0) $machine['overdue_job_count']++;
    unset($machine);
}
$pendingStmt->close();

$rows = [];
$totals = [
    'machine_count' => 0, 'job_count' => 0, 'shift_count' => 0, 'planned_qty' => 0,
    'total_qty' => 0, 'ok_qty' => 0, 'mc_reject_qty' => 0, 'rm_defect_qty' => 0,
    'rework_qty' => 0, 'pending_qty' => 0, 'downtime_min' => 0,
    'cutter_change_downtime_min' => 0, 'pending_job_count' => count($pendingJobs),
    'overdue_job_count' => 0, 'achievement_percent' => 0, 'reject_percent' => 0
];
foreach ($machines as $machine) {
    unset($machine['jobs']);
    $machine['pending_qty'] = max($machine['planned_qty'] - $machine['ok_qty'], 0);
    $machine['achievement_percent'] = $machine['planned_qty'] > 0
        ? round(($machine['ok_qty'] / $machine['planned_qty']) * 100, 1) : 0;
    $reject = $machine['mc_reject_qty'] + $machine['rm_defect_qty'];
    $machine['reject_percent'] = $machine['total_qty'] > 0
        ? round(($reject / $machine['total_qty']) * 100, 1) : 0;
    $rows[] = $machine;
    $totals['machine_count']++;
    foreach (['job_count', 'shift_count', 'planned_qty', 'total_qty', 'ok_qty', 'mc_reject_qty',
        'rm_defect_qty', 'rework_qty', 'pending_qty', 'downtime_min', 'cutter_change_downtime_min',
        'overdue_job_count'] as $field) {
        $totals[$field] += $machine[$field];
    }
}
$totals['achievement_percent'] = $totals['planned_qty'] > 0
    ? round(($totals['ok_qty'] / $totals['planned_qty']) * 100, 1) : 0;
$totalReject = $totals['mc_reject_qty'] + $totals['rm_defect_qty'];
$totals['reject_percent'] = $totals['total_qty'] > 0
    ? round(($totalReject / $totals['total_qty']) * 100, 1) : 0;

json_response([
    'period' => $period, 'period_label' => $label,
    'start_date' => $startDate, 'end_date' => $endDate,
    'filters' => ['machine_id' => $machineId, 'part_id' => $partId, 'operation' => $operation],
    'rows' => $rows, 'pending_jobs' => $pendingJobs, 'issues' => $issues,
    'totals' => $totals, 'generated_at' => date('Y-m-d H:i:s'),
    'source_note' => 'Actual production jobs and shift entries only. ISO Weekly/Monthly Plans are not used.'
]);
$conn->close();
?>
