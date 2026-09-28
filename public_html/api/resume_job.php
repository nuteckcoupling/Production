<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
$shift_date = trim($data['shift_date'] ?? '');
$shift = trim($data['shift'] ?? '');
$remarks = trim($data['remarks'] ?? '');

$date = DateTime::createFromFormat('Y-m-d', $shift_date);
$shift_master = find_shift($conn, $shift);
if (!$job_id || !$date || $date->format('Y-m-d') !== $shift_date || !$shift_master) {
    json_error('Valid stopped job, date and active shift are required', 400);
}
if (strlen($remarks) > 255) {
    json_error('Resume Remarks must be 255 characters or fewer', 400);
}

$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];
$shift_hours = (float)$shift_master['shift_hours'];
$shift_remarks = $remarks === '' ? null : 'Resume: ' . $remarks;

try {
    $conn->begin_transaction();

    $job_stmt = $conn->prepare("SELECT id, status FROM production_jobs WHERE id = ? FOR UPDATE");
    $job_stmt->bind_param('i', $job_id);
    $job_stmt->execute();
    $job = $job_stmt->get_result()->fetch_assoc();
    $job_stmt->close();
    if (!$job) throw new RuntimeException('Job not found', 404);
    if ($job['status'] !== 'Stopped') throw new RuntimeException('Only a stopped job can be resumed', 409);

    $running_stmt = $conn->prepare("SELECT id FROM job_shifts WHERE job_id = ? AND status = 'Running' FOR UPDATE");
    $running_stmt->bind_param('i', $job_id);
    $running_stmt->execute();
    $running_shift = $running_stmt->get_result()->fetch_assoc();
    $running_stmt->close();
    if ($running_shift) throw new RuntimeException('This job already has a running shift', 409);

    $change_stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM job_cutter_changes WHERE job_id = ? AND status = 'In Progress') +
        (SELECT COUNT(*) FROM job_setting_changes WHERE old_job_id = ? AND status = 'In Progress') AS active_changes");
    $change_stmt->bind_param('ii', $job_id, $job_id);
    $change_stmt->execute();
    $active_changes = (int)$change_stmt->get_result()->fetch_assoc()['active_changes'];
    $change_stmt->close();
    if ($active_changes > 0) throw new RuntimeException('Complete the active cutter or setting change before resuming', 409);

    $shift_stmt = $conn->prepare("INSERT INTO job_shifts
        (job_id, shift_date, shift, shift_hours, operator_id, started_at, status, machine_status, remarks, started_by_user_id)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Running', 'Running', ?, ?)");
    $shift_stmt->bind_param('issdisi', $job_id, $shift_date, $shift, $shift_hours, $operator_id, $shift_remarks, $user_id);
    $shift_stmt->execute();
    $shift_id = (int)$shift_stmt->insert_id;
    $shift_stmt->close();

    $update_job = $conn->prepare("UPDATE production_jobs SET status = 'Running', current_operator_id = ?,
        current_shift = ?, status_changed_at = NOW() WHERE id = ? AND status = 'Stopped'");
    $update_job->bind_param('isi', $operator_id, $shift, $job_id);
    $update_job->execute();
    if ($update_job->affected_rows !== 1) throw new RuntimeException('Job was already resumed', 409);
    $update_job->close();

    $conn->commit();
    audit_log($conn, $user, 'Production Job', 'Resumed',
        'Resumed Job #' . $job_id . ' on shift ' . $shift . ($remarks === '' ? '' : ' — ' . $remarks),
        'production_job', (int)$job_id);
    json_response([
        'success' => true,
        'job_id' => (int)$job_id,
        'shift_id' => $shift_id,
        'status' => 'Running',
        'operator_name' => $user['operator_name'] ?? $user['username'],
        'shift' => $shift,
        'shift_hours' => $shift_hours
    ]);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    json_error($error->getMessage(), in_array($code, [404, 409], true) ? $code : 400);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    json_error($error->getCode() === 1062 ? 'Job was already resumed' : 'Unable to resume job', $error->getCode() === 1062 ? 409 : 500);
} catch (Throwable $error) {
    $conn->rollback();
    json_error('Unable to resume job', 500);
}

$conn->close();
?>
