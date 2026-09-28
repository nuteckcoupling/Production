<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();
$data = json_input();
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
if (!$job_id) json_error('Valid job is required', 400);

$operator_id = (int)$user['operator_id'];
try {
    $conn->begin_transaction();
    $job_stmt = $conn->prepare("SELECT id, status, current_operator_id, current_shift
        FROM production_jobs WHERE id = ? FOR UPDATE");
    $job_stmt->bind_param('i', $job_id);
    $job_stmt->execute();
    $job = $job_stmt->get_result()->fetch_assoc();
    $job_stmt->close();
    if (!$job) throw new RuntimeException('Job not found', 404);
    if ($job['status'] !== 'Handover Pending' || (int)$job['current_operator_id'] !== $operator_id) {
        throw new RuntimeException('This takeover belongs to the incoming operator', 403);
    }

    $shift_stmt = $conn->prepare("SELECT id, started_at,
            GREATEST(TIMESTAMPDIFF(SECOND, NOW(), started_at), 0) AS remaining_seconds
        FROM job_shifts
        WHERE job_id = ? AND operator_id = ? AND status = 'Running'
          AND remarks LIKE '[TAKEOVER]%' FOR UPDATE");
    $shift_stmt->bind_param('ii', $job_id, $operator_id);
    $shift_stmt->execute();
    $shift = $shift_stmt->get_result()->fetch_assoc();
    $shift_stmt->close();
    if (!$shift) throw new RuntimeException('Pending takeover not found', 409);
    $remaining = (int)$shift['remaining_seconds'];
    if ($remaining > 0) {
        throw new RuntimeException('Takeover can resume in ' . $remaining . ' seconds', 409);
    }

    $shift_id = (int)$shift['id'];
    $activate_shift = $conn->prepare("UPDATE job_shifts SET machine_status = 'Running' WHERE id = ? AND status = 'Running'");
    $activate_shift->bind_param('i', $shift_id);
    $activate_shift->execute();
    $activate_shift->close();

    $update_job = $conn->prepare("UPDATE production_jobs SET status = 'Running', status_changed_at = NOW()
        WHERE id = ? AND status = 'Handover Pending'");
    $update_job->bind_param('i', $job_id);
    $update_job->execute();
    if ($update_job->affected_rows !== 1) throw new RuntimeException('Takeover was already completed', 409);
    $update_job->close();

    audit_log($conn, $user, 'Production Job', 'Emergency Takeover Completed',
        'Completed emergency takeover for Job #' . $job_id . ' on shift ' . $job['current_shift'],
        'production_job', (int)$job_id);
    $conn->commit();
    json_response(['success' => true, 'job_id' => (int)$job_id, 'status' => 'Running']);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    json_error($error->getMessage(), in_array($code, [403, 404, 409], true) ? $code : 400);
} catch (Throwable $error) {
    $conn->rollback();
    json_error('Unable to complete takeover', 500);
}
$conn->close();
?>
