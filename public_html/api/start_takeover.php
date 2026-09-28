<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
$shift_date = trim($data['shift_date'] ?? '');
$shift = trim($data['shift'] ?? '');
$reason = trim($data['reason'] ?? '');
$remarks = trim($data['remarks'] ?? '');
$allowed_reasons = ['Operator Unavailable', 'Shift Not Ended', 'Emergency', 'Supervisor Correction', 'Other'];
$date = DateTime::createFromFormat('Y-m-d', $shift_date);
$shift_master = find_shift($conn, $shift);

if (!$job_id || !$date || $date->format('Y-m-d') !== $shift_date || !$shift_master
    || !in_array($reason, $allowed_reasons, true) || $remarks === '') {
    json_error('Valid job, shift, takeover reason and remarks are required', 400);
}
if (strlen($remarks) > 150) json_error('Takeover Remarks must be 150 characters or fewer', 400);

$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];
$shift_hours = (float)$shift_master['shift_hours'];
$operator_name = $user['operator_name'] ?? $user['username'];
$takeover_remarks = '[TAKEOVER] Reason: ' . $reason . ' | Remarks: ' . $remarks;

try {
    $conn->begin_transaction();

    $job_stmt = $conn->prepare("SELECT id, status, current_operator_id FROM production_jobs WHERE id = ? FOR UPDATE");
    $job_stmt->bind_param('i', $job_id);
    $job_stmt->execute();
    $job = $job_stmt->get_result()->fetch_assoc();
    $job_stmt->close();
    if (!$job) throw new RuntimeException('Job not found', 404);
    if ($job['status'] !== 'Running') throw new RuntimeException('Only a running job can be taken over', 409);
    if ((int)$job['current_operator_id'] === $operator_id) throw new RuntimeException('You are already the current operator', 409);

    $change_stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM job_cutter_changes WHERE job_id = ? AND status = 'In Progress') +
        (SELECT COUNT(*) FROM job_setting_changes WHERE old_job_id = ? AND status = 'In Progress') AS active_changes");
    $change_stmt->bind_param('ii', $job_id, $job_id);
    $change_stmt->execute();
    $active_changes = (int)$change_stmt->get_result()->fetch_assoc()['active_changes'];
    $change_stmt->close();
    if ($active_changes > 0) throw new RuntimeException('Complete the cutter or setting change before takeover', 409);

    $old_shift_stmt = $conn->prepare("SELECT id FROM job_shifts WHERE job_id = ? AND status = 'Running' FOR UPDATE");
    $old_shift_stmt->bind_param('i', $job_id);
    $old_shift_stmt->execute();
    $old_shift = $old_shift_stmt->get_result()->fetch_assoc();
    $old_shift_stmt->close();
    if (!$old_shift) throw new RuntimeException('Running shift not found', 409);
    $old_shift_id = (int)$old_shift['id'];

    $close_shift = $conn->prepare("UPDATE job_shifts SET ended_at = NOW(), status = 'Ended',
        downtime_min = downtime_min + 2, machine_status = 'Idle', job_outcome = 'Continue Next Shift',
        issue_code = COALESCE(NULLIF(issue_code, ''), 'Other'),
        remarks = LEFT(CONCAT_WS(' | ', NULLIF(remarks, ''), ?), 255)
        WHERE id = ? AND status = 'Running'");
    $close_note = 'Emergency takeover by ' . $operator_name . '. Reason: ' . $reason . '. Remarks: ' . $remarks;
    $close_shift->bind_param('si', $close_note, $old_shift_id);
    $close_shift->execute();
    if ($close_shift->affected_rows !== 1) throw new RuntimeException('Shift was already ended', 409);
    $close_shift->close();

    $new_shift = $conn->prepare("INSERT INTO job_shifts
        (job_id, shift_date, shift, shift_hours, operator_id, started_at, status, machine_status, remarks, started_by_user_id)
        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE), 'Running', 'Idle', ?, ?)");
    $new_shift->bind_param('issdisi', $job_id, $shift_date, $shift, $shift_hours, $operator_id, $takeover_remarks, $user_id);
    $new_shift->execute();
    $new_shift_id = (int)$new_shift->insert_id;
    $new_shift->close();

    $update_job = $conn->prepare("UPDATE production_jobs SET status = 'Handover Pending',
        current_operator_id = ?, current_shift = ?, status_changed_at = NOW(),
        remarks = LEFT(CONCAT_WS(' | ', NULLIF(remarks, ''), ?), 255)
        WHERE id = ? AND status = 'Running'");
    $update_job->bind_param('issi', $operator_id, $shift, $takeover_remarks, $job_id);
    $update_job->execute();
    if ($update_job->affected_rows !== 1) throw new RuntimeException('Job status changed before takeover', 409);
    $update_job->close();

    audit_log($conn, $user, 'Production Job', 'Emergency Takeover Requested',
        'Requested takeover of Job #' . $job_id . ' from operator ID ' . $job['current_operator_id'] .
        '. Reason: ' . $reason . '. Remarks: ' . $remarks . '. Resume after 2 minutes.',
        'production_job', (int)$job_id);
    $conn->commit();
    json_response([
        'success' => true,
        'job_id' => (int)$job_id,
        'shift_id' => $new_shift_id,
        'status' => 'Takeover Pending',
        'wait_seconds' => 120,
        'operator_name' => $operator_name
    ]);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    json_error($error->getMessage(), in_array($code, [404, 409], true) ? $code : 400);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    json_error($error->getCode() === 1062 ? 'A takeover or shift is already active' : 'Unable to start takeover', $error->getCode() === 1062 ? 409 : 500);
} catch (Throwable $error) {
    $conn->rollback();
    json_error('Unable to start takeover', 500);
}
$conn->close();
?>
