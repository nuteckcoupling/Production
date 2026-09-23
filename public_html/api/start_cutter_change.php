<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
$new_cutter_id = filter_var($data['new_cutter_id'] ?? null, FILTER_VALIDATE_INT);
$reason = trim($data['reason'] ?? '');
$remarks = trim($data['remarks'] ?? '');

if (!$job_id || !$new_cutter_id || $reason === '') {
    http_response_code(400);
    json_response(['error' => 'Job, new cutter and reason are required']);
    exit;
}
if (strlen($reason) > 150 || strlen($remarks) > 255) {
    http_response_code(400);
    json_response(['error' => 'Reason or remarks are too long']);
    exit;
}

$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];
$remarks = $remarks === '' ? null : $remarks;

try {
    $conn->begin_transaction();

    $jobStmt = $conn->prepare("SELECT id, status, current_operator_id, cutter_id FROM production_jobs WHERE id = ? FOR UPDATE");
    $jobStmt->bind_param("i", $job_id);
    $jobStmt->execute();
    $job = $jobStmt->get_result()->fetch_assoc();
    $jobStmt->close();
    if (!$job) throw new RuntimeException('Job not found', 404);
    if ($job['status'] !== 'Running') throw new RuntimeException('Cutter can be changed only on a running job', 409);
    if ((int)$job['current_operator_id'] !== $operator_id) throw new RuntimeException('Only the current operator can change the cutter', 403);
    if ($job['cutter_id'] !== null && (int)$job['cutter_id'] === (int)$new_cutter_id) {
        throw new RuntimeException('Select a different cutter', 400);
    }

    $shiftStmt = $conn->prepare("SELECT id FROM job_shifts WHERE job_id = ? AND operator_id = ? AND status = 'Running' FOR UPDATE");
    $shiftStmt->bind_param("ii", $job_id, $operator_id);
    $shiftStmt->execute();
    $shift = $shiftStmt->get_result()->fetch_assoc();
    $settingStmt = $conn->prepare("SELECT id FROM job_setting_changes WHERE old_job_id = ? AND status = 'In Progress' FOR UPDATE");
    $settingStmt->bind_param("i", $job_id);
    $settingStmt->execute();
    $activeSetting = $settingStmt->get_result()->fetch_assoc();
    $settingStmt->close();
    if ($activeSetting) throw new RuntimeException('Complete the setting change before changing the cutter', 409);

    $shiftStmt->close();
    if (!$shift) throw new RuntimeException('Running shift not found for this operator', 409);
    $shift_id = (int)$shift['id'];

    $cutterStmt = $conn->prepare("SELECT id, cutter_num FROM cutters WHERE id = ? AND status = 'Active'");
    $cutterStmt->bind_param("i", $new_cutter_id);
    $cutterStmt->execute();
    $newCutter = $cutterStmt->get_result()->fetch_assoc();
    $cutterStmt->close();
    if (!$newCutter) throw new RuntimeException('Select a valid active cutter', 400);

    $old_cutter_id = $job['cutter_id'] === null ? null : (int)$job['cutter_id'];
    $insert = $conn->prepare("INSERT INTO job_cutter_changes
        (job_id, shift_id, old_cutter_id, new_cutter_id, reason, remarks, started_at, status, started_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'In Progress', ?)");
    $insert->bind_param("iiiissi", $job_id, $shift_id, $old_cutter_id, $new_cutter_id, $reason, $remarks, $user_id);
    $insert->execute();
    $change_id = $insert->insert_id;
    $insert->close();

    $conn->commit();
    json_response(['success' => true, 'change_id' => (int)$change_id, 'job_id' => (int)$job_id,
        'new_cutter_id' => (int)$new_cutter_id, 'new_cutter_num' => $newCutter['cutter_num'],
        'status' => 'In Progress']);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    if ($error->getCode() === 1062) {
        http_response_code(409);
        json_response(['error' => 'A cutter change is already in progress']);
    } else {
        http_response_code(500);
        json_response(['error' => 'Unable to start cutter change']);
    }
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    http_response_code(in_array($code, [400, 403, 404, 409], true) ? $code : 400);
    json_response(['error' => $error->getMessage()]);
}
$conn->close();
?>
