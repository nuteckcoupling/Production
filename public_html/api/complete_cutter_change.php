<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$job_id = filter_var($data['job_id'] ?? null, FILTER_VALIDATE_INT);
if (!$job_id) {
    http_response_code(400);
    json_response(['error' => 'Valid job is required']);
    exit;
}
$operator_id = (int)$user['operator_id'];
$user_id = (int)$user['id'];

try {
    $conn->begin_transaction();

    $jobStmt = $conn->prepare("SELECT id, status, current_operator_id FROM production_jobs WHERE id = ? FOR UPDATE");
    $jobStmt->bind_param("i", $job_id);
    $jobStmt->execute();
    $job = $jobStmt->get_result()->fetch_assoc();
    $jobStmt->close();
    if (!$job) throw new RuntimeException('Job not found', 404);
    if ($job['status'] !== 'Running') throw new RuntimeException('This job is not running', 409);
    if ((int)$job['current_operator_id'] !== $operator_id) throw new RuntimeException('Only the current operator can complete the cutter change', 403);

    $changeStmt = $conn->prepare("SELECT id, new_cutter_id, started_at FROM job_cutter_changes WHERE job_id = ? AND status = 'In Progress' FOR UPDATE");
    $changeStmt->bind_param("i", $job_id);
    $changeStmt->execute();
    $change = $changeStmt->get_result()->fetch_assoc();
    $changeStmt->close();
    if (!$change) throw new RuntimeException('No cutter change is in progress', 409);
    $change_id = (int)$change['id'];
    $new_cutter_id = (int)$change['new_cutter_id'];

    $complete = $conn->prepare("UPDATE job_cutter_changes SET status = 'Completed', completed_at = NOW(), completed_by_user_id = ? WHERE id = ? AND status = 'In Progress'");
    $complete->bind_param("ii", $user_id, $change_id);
    $complete->execute();
    if ($complete->affected_rows !== 1) throw new RuntimeException('Cutter change was already completed', 409);
    $complete->close();

    $updateJob = $conn->prepare("UPDATE production_jobs SET cutter_id = ?, status_changed_at = NOW() WHERE id = ?");
    $updateJob->bind_param("ii", $new_cutter_id, $job_id);
    $updateJob->execute();
    $updateJob->close();

    $durationStmt = $conn->prepare("SELECT TIMESTAMPDIFF(MINUTE, started_at, completed_at) AS downtime_min FROM job_cutter_changes WHERE id = ?");
    $durationStmt->bind_param("i", $change_id);
    $durationStmt->execute();
    $downtime = (int)$durationStmt->get_result()->fetch_assoc()['downtime_min'];
    $durationStmt->close();

    $conn->commit();
    json_response(['success' => true, 'change_id' => $change_id, 'job_id' => (int)$job_id,
        'new_cutter_id' => $new_cutter_id, 'downtime_min' => $downtime, 'status' => 'Completed']);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    http_response_code(in_array($code, [403, 404, 409], true) ? $code : 400);
    json_response(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    json_response(['error' => 'Unable to complete cutter change']);
}
$conn->close();
?>
