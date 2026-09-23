<?php
require __DIR__ . "/bootstrap.php";
$authenticated_user = require_operator_supervisor();

$data = json_input();

if (($authenticated_user['role'] ?? '') === 'Operator/Supervisor') {
    $data['operator_id'] = $authenticated_user['operator_id'];
}

// Basic validation
$required = ["entry_date","shift","machine_id","operator_id","coupling_type","part_id","component"];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        json_response(["error" => "Missing required field: $field"]);
        exit;
    }
}

$allowed_shifts = ['1', '2', '3', 'Day Shift', 'Night Shift'];
if (!in_array($data['shift'], $allowed_shifts, true)) {
    http_response_code(400);
    json_response(["error" => "Invalid shift"]);
    exit;
}
$shift_hours_map = ['1' => 14, '2' => 12, '3' => 12, 'Day Shift' => 12, 'Night Shift' => 12];
$shift_hours = $shift_hours_map[$data['shift']];

$allowed_issue_codes = [
    '',
    'No Operator',
    'No Material',
    'M/C Breakdown (Mechanical)',
    'M/C Breakdown (Electrical)',
    'No Power',
    'New Setting',
    'Other'
];
$issue_code = $data['issue_code'] ?? '';
if (!in_array($issue_code, $allowed_issue_codes, true)) {
    http_response_code(400);
    json_response(["error" => "Invalid Issue / Code"]);
    exit;
}

$partCheck = $conn->prepare("SELECT id FROM parts WHERE id = ? AND coupling_type = ? AND status = 'Active'");
$partCheck->bind_param("is", $data['part_id'], $data['coupling_type']);
$partCheck->execute();
if ($partCheck->get_result()->num_rows !== 1) {
    http_response_code(400);
    json_response(["error" => "Selected part does not belong to the selected coupling range"]);
    exit;
}
$partCheck->close();

$componentCheck = $conn->prepare("SELECT id FROM part_components WHERE part_id = ? AND component_name = ?");
$componentCheck->bind_param("is", $data['part_id'], $data['component']);
$componentCheck->execute();
if ($componentCheck->get_result()->num_rows !== 1) {
    http_response_code(400);
    json_response(["error" => "Selected component is not available for this part"]);
    exit;
}
$componentCheck->close();

$stmt = $conn->prepare("INSERT INTO daily_entries
    (entry_date, shift, shift_hours, machine_id, operator_id, part_id, component, drg_no, operation, cutter_id,
     planned_qty, total_qty, ok_qty, mc_reject_qty, rm_defect_qty, rework_qty,
     machine_status, downtime_min, issue_code, remarks)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$cutter_id = !empty($data['cutter_id']) ? (int)$data['cutter_id'] : null;

$machine_id = (int)$data['machine_id'];
$part_id = (int)$data['part_id'];
$plannedQtyStmt = $conn->prepare("SELECT COALESCE(MAX(total_qty), 0) AS planned_qty
                                  FROM daily_entries
                                  WHERE machine_id = ? AND part_id = ? AND shift_hours = 12");
$plannedQtyStmt->bind_param("ii", $machine_id, $part_id);
$plannedQtyStmt->execute();
$planned_qty = (int)$plannedQtyStmt->get_result()->fetch_assoc()['planned_qty'];
$plannedQtyStmt->close();

$total_qty = $data['total_qty'] === "" ? 0 : (int)$data['total_qty'];
$ok_qty = $data['ok_qty'] === "" ? 0 : (int)$data['ok_qty'];
$mc_reject_qty = $data['mc_reject_qty'] === "" ? 0 : (int)$data['mc_reject_qty'];
$rm_defect_qty = $data['rm_defect_qty'] === "" ? 0 : (int)$data['rm_defect_qty'];
$rework_qty = $data['rework_qty'] === "" ? 0 : (int)$data['rework_qty'];
$downtime_min = $data['downtime_min'] === "" ? 0 : (int)$data['downtime_min'];

$drg_no = isset($data['drg_no']) ? $data['drg_no'] : '';

$stmt->bind_param(
    "ssiiiisssiiiiiiisiss",
    $data['entry_date'],
    $data['shift'],
    $shift_hours,
    $machine_id,
    $data['operator_id'],
    $part_id,
    $data['component'],
    $drg_no,
    $data['operation'],
    $cutter_id,
    $planned_qty,
    $total_qty,
    $ok_qty,
    $mc_reject_qty,
    $rm_defect_qty,
    $rework_qty,
    $data['machine_status'],
    $downtime_min,
    $issue_code,
    $data['remarks']
);

if ($stmt->execute()) {
    json_response(["success" => true, "id" => $stmt->insert_id]);
} else {
    http_response_code(500);
    json_response(["error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
