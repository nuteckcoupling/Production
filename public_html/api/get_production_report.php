<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$period = $_GET['period'] ?? 'daily';
$value = trim($_GET['value'] ?? '');
$today = new DateTime('today');

if ($period === 'daily') {
    $date = $value !== '' ? DateTime::createFromFormat('!Y-m-d', $value) : clone $today;
    if (!$date || $date->format('Y-m-d') !== ($value !== '' ? $value : $today->format('Y-m-d'))) {
        http_response_code(400);
        json_response(['error' => 'Invalid daily report date']);
        exit;
    }
    $start = clone $date;
    $end = clone $date;
    $label = $date->format('d M Y');
} elseif ($period === 'weekly') {
    $date = $value !== '' ? DateTime::createFromFormat('!Y-m-d', $value) : clone $today;
    if (!$date || $date->format('Y-m-d') !== ($value !== '' ? $value : $today->format('Y-m-d'))) {
        http_response_code(400);
        json_response(['error' => 'Invalid weekly report date']);
        exit;
    }
    $start = clone $date;
    $start->modify('monday this week');
    $end = clone $start;
    $end->modify('+6 days');
    $label = $start->format('d M Y') . ' - ' . $end->format('d M Y');
} elseif ($period === 'monthly') {
    $monthValue = $value !== '' ? $value : $today->format('Y-m');
    $date = DateTime::createFromFormat('!Y-m', $monthValue);
    if (!$date || $date->format('Y-m') !== $monthValue) {
        http_response_code(400);
        json_response(['error' => 'Invalid monthly report month']);
        exit;
    }
    $start = clone $date;
    $end = clone $date;
    $end->modify('last day of this month');
    $label = $date->format('F Y');
} else {
    http_response_code(400);
    json_response(['error' => 'Invalid report period']);
    exit;
}

$startDate = $start->format('Y-m-d');
$endDate = $end->format('Y-m-d');
$filterMachineId = filter_var($_GET['machine_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$operatorId = filter_var($_GET['operator_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$partId = filter_var($_GET['part_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$shiftFilter = trim($_GET['shift'] ?? '');
$couplingFilter = trim($_GET['coupling_type'] ?? '');
if ($shiftFilter !== '') {
    $shiftCheck = find_shift($conn, $shiftFilter, false);
    if (!$shiftCheck) {
        json_error('Invalid shift filter', 400);
    }
}
$stmt = $conn->prepare("SELECT
        s.id AS shift_id, s.shift_date, s.shift, s.shift_hours, s.total_qty, s.ok_qty,
        s.mc_reject_qty, s.rm_defect_qty, s.rework_qty, s.downtime_min,
        j.id AS job_id, j.planned_qty, j.status AS job_status, j.component,
        m.id AS machine_id, m.code AS machine_code, m.name AS machine_name,
        o.name AS operator_name, p.part_name
    FROM job_shifts s
    JOIN production_jobs j ON j.id = s.job_id
    JOIN machines m ON m.id = j.machine_id
    JOIN operators o ON o.id = s.operator_id
    JOIN parts p ON p.id = j.part_id
    WHERE s.shift_date BETWEEN ? AND ?
      AND (? = 0 OR m.id = ?)
      AND (? = '' OR s.shift = ?)
      AND (? = 0 OR o.id = ?)
      AND (? = '' OR p.coupling_type = ?)
      AND (? = 0 OR p.id = ?)
    ORDER BY m.code, s.shift_date, s.id");
$stmt->bind_param("ssiissiissii", $startDate, $endDate, $filterMachineId, $filterMachineId,
    $shiftFilter, $shiftFilter, $operatorId, $operatorId, $couplingFilter, $couplingFilter,
    $partId, $partId);
$stmt->execute();
$result = $stmt->get_result();

$machines = [];
while ($record = $result->fetch_assoc()) {
    $machineId = (int)$record['machine_id'];
    if (!isset($machines[$machineId])) {
        $machines[$machineId] = [
            'machine_id' => $machineId,
            'machine_code' => $record['machine_code'],
            'machine_name' => $record['machine_name'],
            'operators' => [], 'parts' => [], 'components' => [], 'shifts' => [], 'statuses' => [],
            'planned_jobs' => [], 'job_count' => 0, 'shift_count' => 0,
            'planned_qty' => 0, 'total_qty' => 0, 'ok_qty' => 0, 'mc_reject_qty' => 0,
            'rm_defect_qty' => 0, 'rework_qty' => 0, 'downtime_min' => 0
        ];
    }
    $row =& $machines[$machineId];
    $jobId = (int)$record['job_id'];
    if (!isset($row['planned_jobs'][$jobId])) {
        $row['planned_jobs'][$jobId] = true;
        $row['planned_qty'] += (int)$record['planned_qty'];
        $row['job_count']++;
    }
    $row['shift_count']++;
    $row['total_qty'] += (int)$record['total_qty'];
    $row['ok_qty'] += (int)$record['ok_qty'];
    $row['mc_reject_qty'] += (int)$record['mc_reject_qty'];
    $row['rm_defect_qty'] += (int)$record['rm_defect_qty'];
    $row['rework_qty'] += (int)$record['rework_qty'];
    $row['downtime_min'] += (int)$record['downtime_min'];
    $row['operators'][$record['operator_name']] = true;
    $row['parts'][$record['part_name']] = true;
    $row['components'][$record['component']] = true;
    $shiftLabel = in_array($record['shift'], ['1', '2', '3'], true) ? 'Shift ' . $record['shift'] : $record['shift'];
    $row['shifts'][$shiftLabel] = true;
    $row['statuses'][$record['job_status']] = true;
    unset($row);
}
$stmt->close();

$rows = [];
$totals = [
    'machine_count' => 0, 'job_count' => 0, 'shift_count' => 0, 'planned_qty' => 0,
    'total_qty' => 0, 'ok_qty' => 0, 'mc_reject_qty' => 0, 'rm_defect_qty' => 0,
    'rework_qty' => 0, 'pending_qty' => 0, 'downtime_min' => 0, 'achievement_percent' => 0
];
foreach ($machines as $machine) {
    $machine['operators'] = implode(', ', array_keys($machine['operators']));
    $machine['parts'] = implode(', ', array_keys($machine['parts']));
    $machine['components'] = implode(', ', array_keys($machine['components']));
    $machine['shifts'] = implode(', ', array_keys($machine['shifts']));
    $machine['statuses'] = implode(', ', array_keys($machine['statuses']));
    unset($machine['planned_jobs']);
    $machine['pending_qty'] = max($machine['planned_qty'] - $machine['ok_qty'], 0);
    $machine['achievement_percent'] = $machine['planned_qty'] > 0
        ? round(($machine['ok_qty'] / $machine['planned_qty']) * 100, 1) : 0;
    $rows[] = $machine;

    $totals['machine_count']++;
    foreach (['job_count', 'shift_count', 'planned_qty', 'total_qty', 'ok_qty', 'mc_reject_qty',
        'rm_defect_qty', 'rework_qty', 'pending_qty', 'downtime_min'] as $field) {
        $totals[$field] += $machine[$field];
    }
}
$totals['achievement_percent'] = $totals['planned_qty'] > 0
    ? round(($totals['ok_qty'] / $totals['planned_qty']) * 100, 1) : 0;

json_response([
    'period' => $period,
    'period_label' => $label,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'filters' => [
        'machine_id' => $filterMachineId, 'shift' => $shiftFilter, 'operator_id' => $operatorId,
        'coupling_type' => $couplingFilter, 'part_id' => $partId
    ],
    'rows' => $rows,
    'totals' => $totals,
    'generated_at' => date('Y-m-d H:i:s')
]);
$conn->close();
?>
