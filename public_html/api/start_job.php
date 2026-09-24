<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$required = ['entry_date', 'shift', 'machine_id', 'coupling_type', 'part_id', 'component'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        json_response(['error' => "Missing required field: $field"]);
        exit;
    }
}

$shift_master = find_shift($conn, trim($data['shift']));
if (!$shift_master) {
    json_error('Select a valid active shift', 400);
}
$shift_hours = (float)$shift_master['shift_hours'];

$date = DateTime::createFromFormat('Y-m-d', $data['entry_date']);
if (!$date || $date->format('Y-m-d') !== $data['entry_date']) {
    http_response_code(400);
    json_response(['error' => 'Invalid date']);
    exit;
}

$machine_id = (int)$data['machine_id'];
$part_id = (int)$data['part_id'];
$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];
$component = trim($data['component']);
$drg_no = trim($data['drg_no'] ?? '');
$operation = trim($data['operation'] ?? '');
$cutter_id = !empty($data['cutter_id']) ? (int)$data['cutter_id'] : null;

$machineCheck = $conn->prepare("SELECT id FROM machines WHERE id = ? AND status = 'Active'");
$machineCheck->bind_param("i", $machine_id);
$machineCheck->execute();
if ($machineCheck->get_result()->num_rows !== 1) {
    http_response_code(400);
    json_response(['error' => 'Select a valid active machine']);
    exit;
}
$machineCheck->close();

$partCheck = $conn->prepare("SELECT id FROM parts WHERE id = ? AND coupling_type = ? AND status = 'Active'");
$partCheck->bind_param("is", $part_id, $data['coupling_type']);
$partCheck->execute();
if ($partCheck->get_result()->num_rows !== 1) {
    http_response_code(400);
    json_response(['error' => 'Selected part does not belong to the selected coupling range']);
    exit;
}
$partCheck->close();

$componentCheck = $conn->prepare("SELECT id FROM part_components WHERE part_id = ? AND component_name = ?");
$componentCheck->bind_param("is", $part_id, $component);
$componentCheck->execute();
if ($componentCheck->get_result()->num_rows !== 1) {
    http_response_code(400);
    json_response(['error' => 'Selected component is not available for this part']);
    exit;
}
$componentCheck->close();

if ($cutter_id !== null) {
    $cutterCheck = $conn->prepare("SELECT id FROM cutters WHERE id = ? AND status = 'Active'");
    $cutterCheck->bind_param("i", $cutter_id);
    $cutterCheck->execute();
    if ($cutterCheck->get_result()->num_rows !== 1) {
        http_response_code(400);
        json_response(['error' => 'Select a valid active cutter']);
        exit;
    }
    $cutterCheck->close();
}

$plannedStmt = $conn->prepare("SELECT COALESCE(MAX(total_qty), 0) AS planned_qty
                               FROM daily_entries
                               WHERE machine_id = ? AND part_id = ? AND shift_hours = 12");
$plannedStmt->bind_param("ii", $machine_id, $part_id);
$plannedStmt->execute();
$planned_qty = (int)$plannedStmt->get_result()->fetch_assoc()['planned_qty'];
$plannedStmt->close();

try {
    $conn->begin_transaction();

    $jobStmt = $conn->prepare("INSERT INTO production_jobs
        (machine_id, part_id, component, drg_no, operation, cutter_id, planned_qty, status,
         current_operator_id, current_shift, started_at, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Running', ?, ?, NOW(), ?)");
    $jobStmt->bind_param(
        "iisssiiisi",
        $machine_id,
        $part_id,
        $component,
        $drg_no,
        $operation,
        $cutter_id,
        $planned_qty,
        $operator_id,
        $data['shift'],
        $user_id
    );
    $jobStmt->execute();
    $job_id = $jobStmt->insert_id;
    $jobStmt->close();

    $shiftStmt = $conn->prepare("INSERT INTO job_shifts
        (job_id, shift_date, shift, shift_hours, operator_id, started_at, status, started_by_user_id)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Running', ?)");
    $shiftStmt->bind_param("issdii", $job_id, $data['entry_date'], $data['shift'], $shift_hours, $operator_id, $user_id);
    $shiftStmt->execute();
    $shift_id = $shiftStmt->insert_id;
    $shiftStmt->close();

    $conn->commit();
    audit_log($conn, $user, 'Production Job', 'Started',
        'Started Job #' . $job_id . ' on machine ID ' . $machine_id . ' for shift ' . $data['shift'],
        'production_job', (int)$job_id);
    http_response_code(201);
    json_response([
        'success' => true,
        'job_id' => $job_id,
        'shift_id' => $shift_id,
        'planned_qty' => $planned_qty,
        'status' => 'Running'
    ]);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    if ($error->getCode() === 1062) {
        http_response_code(409);
        json_response(['error' => 'Machine already has an active job']);
    } else {
        http_response_code(500);
        json_response(['error' => 'Unable to start job']);
    }
}

$conn->close();
?>
