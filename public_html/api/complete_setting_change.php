<?php
require "config.php";
require "auth.php";
$user = require_operator_supervisor();

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
if (!$job_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid job is required']);
    exit;
}

$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];

try {
    $conn->begin_transaction();

    $changeStmt = $conn->prepare("SELECT sc.*, TIMESTAMPDIFF(MINUTE, sc.started_at, NOW()) AS downtime_min,
            js.shift_date, js.shift, js.shift_hours
        FROM job_setting_changes sc
        JOIN job_shifts js ON js.id = sc.old_shift_id
        WHERE sc.old_job_id = ? AND sc.status = 'In Progress' FOR UPDATE");
    $changeStmt->bind_param("i", $job_id);
    $changeStmt->execute();
    $change = $changeStmt->get_result()->fetch_assoc();
    $changeStmt->close();
    if (!$change) throw new RuntimeException('No setting change is in progress', 409);
    if ((int)$change['operator_id'] !== $operator_id) {
        throw new RuntimeException('Only the current operator can complete the setting change', 403);
    }

    $jobStmt = $conn->prepare("SELECT id, status, current_operator_id, cumulative_ok_qty, planned_qty
        FROM production_jobs WHERE id = ? FOR UPDATE");
    $jobStmt->bind_param("i", $job_id);
    $jobStmt->execute();
    $job = $jobStmt->get_result()->fetch_assoc();
    $jobStmt->close();
    if (!$job) throw new RuntimeException('Job not found', 404);
    if ($job['status'] !== 'Running') throw new RuntimeException('The current job is no longer running', 409);
    if ((int)$job['current_operator_id'] !== $operator_id) {
        throw new RuntimeException('Only the current operator can complete the setting change', 403);
    }

    $shift_id = (int)$change['old_shift_id'];
    $downtime = max(0, (int)$change['downtime_min']);
    $summaryRemarks = 'Setting change: ' . $change['reason'];
    if (!empty($change['remarks'])) $summaryRemarks .= ' - ' . $change['remarks'];
    $summaryRemarks = substr($summaryRemarks, 0, 255);

    $updateShift = $conn->prepare("UPDATE job_shifts SET ended_at = NOW(), status = 'Ended',
        total_qty = ?, ok_qty = ?, mc_reject_qty = ?, rm_defect_qty = ?, rework_qty = ?,
        downtime_min = ?, machine_status = 'Running', job_outcome = 'Job Stopped',
        issue_code = 'New Setting', remarks = ? WHERE id = ? AND status = 'Running'");
    $updateShift->bind_param("iiiiiisi", $change['total_qty'], $change['ok_qty'],
        $change['mc_reject_qty'], $change['rm_defect_qty'], $change['rework_qty'],
        $downtime, $summaryRemarks, $shift_id);
    $updateShift->execute();
    if ($updateShift->affected_rows !== 1) throw new RuntimeException('Running shift was already ended', 409);
    $updateShift->close();

    $newCumulative = (int)$job['cumulative_ok_qty'] + (int)$change['ok_qty'];
    $oldStatus = 'Completed';
    $updateJob = $conn->prepare("UPDATE production_jobs SET cumulative_ok_qty = ?, status = 'Completed',
        status_changed_at = NOW(), completed_at = NOW(),
        remarks = ? WHERE id = ?");
    $updateJob->bind_param("isi", $newCumulative, $summaryRemarks, $job_id);
    $updateJob->execute();
    $updateJob->close();

    $newJobStmt = $conn->prepare("INSERT INTO production_jobs
        (machine_id, part_id, component, drg_no, operation, cutter_id, planned_qty, status,
         current_operator_id, current_shift, started_at, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Running', ?, ?, NOW(), ?)");
    $newJobStmt->bind_param("iisssiiisi", $change['machine_id'], $change['new_part_id'],
        $change['new_component'], $change['new_drg_no'], $change['new_operation'],
        $change['new_cutter_id'], $change['new_planned_qty'], $operator_id, $change['shift'], $user_id);
    $newJobStmt->execute();
    $new_job_id = $newJobStmt->insert_id;
    $newJobStmt->close();

    $newShiftStmt = $conn->prepare("INSERT INTO job_shifts
        (job_id, shift_date, shift, shift_hours, operator_id, started_at, status, started_by_user_id)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Running', ?)");
    $newShiftStmt->bind_param("issiii", $new_job_id, $change['shift_date'], $change['shift'],
        $change['shift_hours'], $operator_id, $user_id);
    $newShiftStmt->execute();
    $new_shift_id = $newShiftStmt->insert_id;
    $newShiftStmt->close();

    $change_id = (int)$change['id'];
    $complete = $conn->prepare("UPDATE job_setting_changes SET status = 'Completed', completed_at = NOW(),
        new_job_id = ?, completed_by_user_id = ? WHERE id = ? AND status = 'In Progress'");
    $complete->bind_param("iii", $new_job_id, $user_id, $change_id);
    $complete->execute();
    if ($complete->affected_rows !== 1) throw new RuntimeException('Setting change was already completed', 409);
    $complete->close();

    $conn->commit();
    echo json_encode(['success' => true, 'change_id' => $change_id, 'old_job_id' => (int)$job_id,
        'old_job_status' => $oldStatus, 'new_job_id' => (int)$new_job_id,
        'new_shift_id' => (int)$new_shift_id, 'downtime_min' => $downtime, 'status' => 'Completed']);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    if ($error->getCode() === 1062) {
        http_response_code(409);
        echo json_encode(['error' => 'Another active job or setting change already exists on this machine']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Unable to complete setting change']);
    }
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    http_response_code(in_array($code, [403, 404, 409], true) ? $code : 400);
    echo json_encode(['error' => $error->getMessage()]);
}
$conn->close();
?>
