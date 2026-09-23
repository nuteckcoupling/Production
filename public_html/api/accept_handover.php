<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
$shift_date = $data['shift_date'] ?? '';
$shift = $data['shift'] ?? '';
$allowed_shifts = ['1', '2', '3', 'Day Shift', 'Night Shift'];
$shift_hours_map = ['1' => 14, '2' => 12, '3' => 12, 'Day Shift' => 12, 'Night Shift' => 12];

$date = DateTime::createFromFormat('Y-m-d', $shift_date);
if (!$job_id || !$date || $date->format('Y-m-d') !== $shift_date || !in_array($shift, $allowed_shifts, true)) {
    http_response_code(400);
    json_response(['error' => 'Valid job, date and shift are required']);
    exit;
}

$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];
$shift_hours = $shift_hours_map[$shift];

try {
    $conn->begin_transaction();

    $jobStmt = $conn->prepare("SELECT id, status, current_operator_id FROM production_jobs WHERE id = ? FOR UPDATE");
    $jobStmt->bind_param("i", $job_id);
    $jobStmt->execute();
    $job = $jobStmt->get_result()->fetch_assoc();
    $jobStmt->close();

    if (!$job) {
        throw new RuntimeException('Job not found', 404);
    }
    if ($job['status'] !== 'Handover Pending') {
        throw new RuntimeException('This job is not waiting for handover', 409);
    }
    if ((int)$job['current_operator_id'] === $operator_id) {
        throw new RuntimeException('Handover must be accepted by the next operator', 403);
    }

    $runningStmt = $conn->prepare("SELECT id FROM job_shifts WHERE job_id = ? AND status = 'Running' FOR UPDATE");
    $runningStmt->bind_param("i", $job_id);
    $runningStmt->execute();
    $running = $runningStmt->get_result()->fetch_assoc();
    $runningStmt->close();
    if ($running) {
        throw new RuntimeException('A shift is already running for this job', 409);
    }

    $shiftStmt = $conn->prepare("INSERT INTO job_shifts
        (job_id, shift_date, shift, shift_hours, operator_id, started_at, status, started_by_user_id)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Running', ?)");
    $shiftStmt->bind_param("issiii", $job_id, $shift_date, $shift, $shift_hours, $operator_id, $user_id);
    $shiftStmt->execute();
    $shift_id = $shiftStmt->insert_id;
    $shiftStmt->close();

    $updateJob = $conn->prepare("UPDATE production_jobs SET status = 'Running', current_operator_id = ?,
        current_shift = ?, status_changed_at = NOW() WHERE id = ? AND status = 'Handover Pending'");
    $updateJob->bind_param("isi", $operator_id, $shift, $job_id);
    $updateJob->execute();
    if ($updateJob->affected_rows !== 1) {
        throw new RuntimeException('Handover was already accepted', 409);
    }
    $updateJob->close();

    $conn->commit();
    json_response(['success' => true, 'job_id' => (int)$job_id, 'shift_id' => (int)$shift_id,
        'status' => 'Running', 'operator_name' => $user['operator_name'] ?? $user['username'],
        'shift' => $shift, 'shift_hours' => $shift_hours]);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    http_response_code(in_array($code, [403, 404, 409], true) ? $code : 400);
    json_response(['error' => $error->getMessage()]);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    if ($error->getCode() === 1062) {
        http_response_code(409);
        json_response(['error' => 'Handover was already accepted']);
    } else {
        http_response_code(500);
        json_response(['error' => 'Unable to accept handover']);
    }
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    json_response(['error' => 'Unable to accept handover']);
}

$conn->close();
?>
