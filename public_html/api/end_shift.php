<?php
require "config.php";
require "auth.php";
$user = require_operator_supervisor();

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
$allowed_outcomes = ['Continue Next Shift', 'Job Completed', 'Job Stopped'];
$allowed_machine_statuses = ['Running', 'Idle', 'Breakdown'];
$allowed_issues = ['', 'No Operator', 'No Material', 'M/C Breakdown (Mechanical)',
    'M/C Breakdown (Electrical)', 'No Power', 'New Setting', 'Other'];

if (!$job_id || !in_array($data['job_outcome'] ?? '', $allowed_outcomes, true)
    || !in_array($data['machine_status'] ?? '', $allowed_machine_statuses, true)
    || !in_array($data['issue_code'] ?? '', $allowed_issues, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid End Shift details']);
    exit;
}

$quantity_fields = ['total_qty', 'ok_qty', 'mc_reject_qty', 'rm_defect_qty', 'rework_qty', 'downtime_min'];
$values = [];
foreach ($quantity_fields as $field) {
    $raw = $data[$field] ?? 0;
    if (filter_var($raw, FILTER_VALIDATE_INT) === false || (int)$raw < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Quantities and downtime must be whole numbers of zero or more']);
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
$issue_code = trim($data['issue_code'] ?? '');
$issue_code = $issue_code === '' ? null : $issue_code;
$remarks = trim($data['remarks'] ?? '');
$remarks = $remarks === '' ? null : $remarks;
$machine_status = $data['machine_status'];
$job_outcome = $data['job_outcome'];

try {
    $conn->begin_transaction();

    $jobStmt = $conn->prepare("SELECT id, status, current_operator_id, cumulative_ok_qty FROM production_jobs WHERE id = ? FOR UPDATE");
    $jobStmt->bind_param("i", $job_id);
    $jobStmt->execute();
    $job = $jobStmt->get_result()->fetch_assoc();
    $jobStmt->close();

    if (!$job) {
        throw new RuntimeException('Job not found', 404);
    }
    if ($job['status'] !== 'Running') {
        throw new RuntimeException('This job does not have a running shift', 409);
    }
    if ((int)$job['current_operator_id'] !== $operator_id) {
        throw new RuntimeException('Only the current operator can end this shift', 403);
    }
    $changeStmt = $conn->prepare("SELECT id FROM job_cutter_changes WHERE job_id = ? AND status = 'In Progress' FOR UPDATE");
    $changeStmt->bind_param("i", $job_id);
    $changeStmt->execute();
    $activeChange = $changeStmt->get_result()->fetch_assoc();
    $changeStmt->close();
    $settingStmt = $conn->prepare("SELECT id FROM job_setting_changes WHERE old_job_id = ? AND status = 'In Progress' FOR UPDATE");
    $settingStmt->bind_param("i", $job_id);
    $settingStmt->execute();
    $activeSetting = $settingStmt->get_result()->fetch_assoc();
    $settingStmt->close();
    if ($activeSetting) throw new RuntimeException('Complete the setting change before ending the shift', 409);

    if ($activeChange) throw new RuntimeException('Complete the cutter change before ending the shift', 409);


    $shiftStmt = $conn->prepare("SELECT id FROM job_shifts WHERE job_id = ? AND status = 'Running' FOR UPDATE");
    $shiftStmt->bind_param("i", $job_id);
    $shiftStmt->execute();
    $shift = $shiftStmt->get_result()->fetch_assoc();
    $shiftStmt->close();
    if (!$shift) {
        throw new RuntimeException('Running shift not found', 409);
    }
    $shift_id = (int)$shift['id'];

    $updateShift = $conn->prepare("UPDATE job_shifts SET ended_at = NOW(), status = 'Ended',
        total_qty = ?, ok_qty = ?, mc_reject_qty = ?, rm_defect_qty = ?, rework_qty = ?,
        downtime_min = ?, machine_status = ?, job_outcome = ?, issue_code = ?, remarks = ?
        WHERE id = ? AND status = 'Running'");
    $updateShift->bind_param("iiiiiissssi", $values['total_qty'], $values['ok_qty'],
        $values['mc_reject_qty'], $values['rm_defect_qty'], $values['rework_qty'],
        $values['downtime_min'], $machine_status, $job_outcome, $issue_code, $remarks, $shift_id);
    $updateShift->execute();
    if ($updateShift->affected_rows !== 1) {
        throw new RuntimeException('Shift was already ended', 409);
    }
    $updateShift->close();

    if ($machine_status === 'Breakdown') {
        $new_status = 'Breakdown';
    } elseif ($job_outcome === 'Continue Next Shift') {
        $new_status = 'Handover Pending';
    } elseif ($job_outcome === 'Job Completed') {
        $new_status = 'Completed';
    } else {
        $new_status = 'Stopped';
    }
    $new_cumulative = (int)$job['cumulative_ok_qty'] + $values['ok_qty'];

    $updateJob = $conn->prepare("UPDATE production_jobs SET cumulative_ok_qty = ?, status = ?,
        status_changed_at = NOW(), completed_at = CASE WHEN ? = 'Completed' THEN NOW() ELSE NULL END,
        remarks = ? WHERE id = ?");
    $updateJob->bind_param("isssi", $new_cumulative, $new_status, $new_status, $remarks, $job_id);
    $updateJob->execute();
    $updateJob->close();

    $conn->commit();
    echo json_encode(['success' => true, 'job_id' => (int)$job_id, 'status' => $new_status,
        'cumulative_ok_qty' => $new_cumulative]);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    http_response_code(in_array($code, [403, 404, 409], true) ? $code : 400);
    echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Unable to end shift']);
}

$conn->close();
?>
