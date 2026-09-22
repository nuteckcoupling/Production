<?php
require "config.php";
require "auth.php";
$user = require_operator_supervisor();

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
$new_part_id = filter_var($data['new_part_id'] ?? null, FILTER_VALIDATE_INT);
$new_cutter_id = empty($data['new_cutter_id']) ? null : filter_var($data['new_cutter_id'], FILTER_VALIDATE_INT);
$new_component = trim($data['new_component'] ?? '');
$new_drg_no = trim($data['new_drg_no'] ?? '');
$new_operation = trim($data['new_operation'] ?? '');
$reason = trim($data['reason'] ?? '');
$remarks = trim($data['remarks'] ?? '');

if (!$job_id || !$new_part_id || $new_component === '' || $reason === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Job, new part, component and reason are required']);
    exit;
}
if ($new_cutter_id === false || strlen($new_component) > 50 || strlen($new_drg_no) > 100
    || strlen($new_operation) > 150 || strlen($reason) > 150 || strlen($remarks) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or too-long setting details']);
    exit;
}

$quantity_fields = ['total_qty', 'ok_qty', 'mc_reject_qty', 'rm_defect_qty', 'rework_qty'];
$values = [];
foreach ($quantity_fields as $field) {
    $raw = $data[$field] ?? 0;
    if (filter_var($raw, FILTER_VALIDATE_INT) === false || (int)$raw < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Production quantities must be whole numbers of zero or more']);
        exit;
    }
    $values[$field] = (int)$raw;
}
$accounted_qty = $values['ok_qty'] + $values['mc_reject_qty'] + $values['rm_defect_qty'] + $values['rework_qty'];
if ($accounted_qty > $values['total_qty']) {
    http_response_code(400);
    echo json_encode(['error' => 'OK + Reject + Defect + Rework cannot exceed Total Qty']);
    exit;
}

$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];
$new_drg_no = $new_drg_no === '' ? null : $new_drg_no;
$new_operation = $new_operation === '' ? null : $new_operation;
$remarks = $remarks === '' ? null : $remarks;

try {
    $conn->begin_transaction();

    $jobStmt = $conn->prepare("SELECT id, machine_id, part_id, component, operation, cutter_id, status, current_operator_id
        FROM production_jobs WHERE id = ? FOR UPDATE");
    $jobStmt->bind_param("i", $job_id);
    $jobStmt->execute();
    $job = $jobStmt->get_result()->fetch_assoc();
    $jobStmt->close();
    if (!$job) throw new RuntimeException('Job not found', 404);
    if ($job['status'] !== 'Running') throw new RuntimeException('Setting can be changed only on a running job', 409);
    if ((int)$job['current_operator_id'] !== $operator_id) {
        throw new RuntimeException('Only the current operator can change the setting', 403);
    }

    $cutterChange = $conn->prepare("SELECT id FROM job_cutter_changes WHERE job_id = ? AND status = 'In Progress' FOR UPDATE");
    $cutterChange->bind_param("i", $job_id);
    $cutterChange->execute();
    $activeCutterChange = $cutterChange->get_result()->fetch_assoc();
    $cutterChange->close();
    if ($activeCutterChange) throw new RuntimeException('Complete the cutter change before changing the setting', 409);

    $shiftStmt = $conn->prepare("SELECT id FROM job_shifts WHERE job_id = ? AND operator_id = ? AND status = 'Running' FOR UPDATE");
    $shiftStmt->bind_param("ii", $job_id, $operator_id);
    $shiftStmt->execute();
    $shift = $shiftStmt->get_result()->fetch_assoc();
    $shiftStmt->close();
    if (!$shift) throw new RuntimeException('Running shift not found for this operator', 409);
    $shift_id = (int)$shift['id'];

    $partStmt = $conn->prepare("SELECT id FROM parts WHERE id = ? AND status = 'Active'");
    $partStmt->bind_param("i", $new_part_id);
    $partStmt->execute();
    $part = $partStmt->get_result()->fetch_assoc();
    $partStmt->close();
    if (!$part) throw new RuntimeException('Select a valid active part', 400);

    $componentStmt = $conn->prepare("SELECT id FROM part_components WHERE part_id = ? AND component_name = ?");
    $componentStmt->bind_param("is", $new_part_id, $new_component);
    $componentStmt->execute();
    $component = $componentStmt->get_result()->fetch_assoc();
    $componentStmt->close();
    if (!$component) throw new RuntimeException('Selected component is not available for the new part', 400);

    if ($new_cutter_id !== null) {
        $cutterStmt = $conn->prepare("SELECT id FROM cutters WHERE id = ? AND status = 'Active'");
        $cutterStmt->bind_param("i", $new_cutter_id);
        $cutterStmt->execute();
        $cutter = $cutterStmt->get_result()->fetch_assoc();
        $cutterStmt->close();
        if (!$cutter) throw new RuntimeException('Select a valid active cutter', 400);
    }

    $sameSetting = (int)$job['part_id'] === (int)$new_part_id
        && $job['component'] === $new_component
        && trim((string)$job['operation']) === trim((string)$new_operation)
        && (($job['cutter_id'] === null && $new_cutter_id === null)
            || (int)$job['cutter_id'] === (int)$new_cutter_id);
    if ($sameSetting) throw new RuntimeException('Select a different part, component, operation or cutter', 400);

    $plannedStmt = $conn->prepare("SELECT COALESCE(MAX(total_qty), 0) AS planned_qty
        FROM daily_entries WHERE machine_id = ? AND part_id = ? AND shift_hours = 12");
    $plannedStmt->bind_param("ii", $job['machine_id'], $new_part_id);
    $plannedStmt->execute();
    $new_planned_qty = (int)$plannedStmt->get_result()->fetch_assoc()['planned_qty'];
    $plannedStmt->close();

    $insert = $conn->prepare("INSERT INTO job_setting_changes
        (old_job_id, old_shift_id, machine_id, operator_id, new_part_id, new_component,
         new_drg_no, new_operation, new_cutter_id, new_planned_qty, reason, remarks,
         total_qty, ok_qty, mc_reject_qty, rm_defect_qty, rework_qty, started_at, status, started_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'In Progress', ?)");
    $insert->bind_param("iiiiisssiissiiiiii", $job_id, $shift_id, $job['machine_id'], $operator_id,
        $new_part_id, $new_component, $new_drg_no, $new_operation, $new_cutter_id, $new_planned_qty,
        $reason, $remarks, $values['total_qty'], $values['ok_qty'], $values['mc_reject_qty'],
        $values['rm_defect_qty'], $values['rework_qty'], $user_id);
    $insert->execute();
    $change_id = $insert->insert_id;
    $insert->close();

    $conn->commit();
    echo json_encode(['success' => true, 'change_id' => (int)$change_id, 'job_id' => (int)$job_id,
        'status' => 'In Progress']);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    if ($error->getCode() === 1062) {
        http_response_code(409);
        echo json_encode(['error' => 'A setting change is already in progress on this machine']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Unable to start setting change']);
    }
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    http_response_code(in_array($code, [400, 403, 404, 409], true) ? $code : 400);
    echo json_encode(['error' => $error->getMessage()]);
}
$conn->close();
?>
