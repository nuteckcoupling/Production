<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/maintenance_helpers.php';
$user = require_operator_supervisor();

$machine_id = filter_var($_POST['machine_id'] ?? null, FILTER_VALIDATE_INT);
$breakdown_type = trim((string)($_POST['breakdown_type'] ?? ''));
$description = trim((string)($_POST['problem_description'] ?? ''));
if (!$machine_id || !in_array($breakdown_type, ['Mechanical', 'Electrical', 'Other'], true)) {
    json_error('Valid machine and breakdown type are required', 400);
}
if ($description === '' || strlen($description) > 500) json_error('Problem description is required and must be 500 characters or fewer', 400);

$stored_photo = [];
try {
    $conn->begin_transaction();
    $machine_stmt = $conn->prepare("SELECT id FROM machines WHERE id = ? AND status = 'Active' FOR UPDATE");
    $machine_stmt->bind_param('i', $machine_id);
    $machine_stmt->execute();
    $machine = $machine_stmt->get_result()->fetch_assoc();
    $machine_stmt->close();
    if (!$machine) throw new RuntimeException('Machine not found or inactive', 404);

    $existing_stmt = $conn->prepare("SELECT id FROM maintenance_tickets WHERE machine_id = ? AND status <> 'Closed' LIMIT 1 FOR UPDATE");
    $existing_stmt->bind_param('i', $machine_id);
    $existing_stmt->execute();
    $existing = $existing_stmt->get_result()->fetch_assoc();
    $existing_stmt->close();
    if ($existing) throw new RuntimeException('Machine already has an open breakdown ticket', 409);

    $job_stmt = $conn->prepare("SELECT id, status FROM production_jobs
        WHERE machine_id = ? AND status IN ('Running','Handover Pending','Breakdown','Stopped') LIMIT 1 FOR UPDATE");
    $job_stmt->bind_param('i', $machine_id);
    $job_stmt->execute();
    $job = $job_stmt->get_result()->fetch_assoc();
    $job_stmt->close();
    if ($job && $job['status'] !== 'Running') {
        throw new RuntimeException('Resolve the current ' . $job['status'] . ' job before raising a new breakdown ticket', 409);
    }
    $job_id = $job ? (int)$job['id'] : null;
    $previous_status = $job['status'] ?? null;
    $user_id = (int)$user['id'];
    $insert = $conn->prepare("INSERT INTO maintenance_tickets
        (machine_id, job_id, breakdown_type, problem_description, previous_job_status, reported_by_user_id, breakdown_started_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $insert->bind_param('iisssi', $machine_id, $job_id, $breakdown_type, $description, $previous_status, $user_id);
    $insert->execute();
    $ticket_id = (int)$insert->insert_id;
    $insert->close();
    $ticket_no = 'BD-' . str_pad((string)$ticket_id, 6, '0', STR_PAD_LEFT);
    $number_stmt = $conn->prepare('UPDATE maintenance_tickets SET ticket_no = ? WHERE id = ?');
    $number_stmt->bind_param('si', $ticket_no, $ticket_id);
    $number_stmt->execute();
    $number_stmt->close();

    if ($job_id !== null) {
        $break_job = $conn->prepare("UPDATE production_jobs SET status = 'Breakdown', status_changed_at = NOW() WHERE id = ? AND status = 'Running'");
        $break_job->bind_param('i', $job_id);
        $break_job->execute();
        if ($break_job->affected_rows !== 1) throw new RuntimeException('Job status changed before the breakdown could be recorded', 409);
        $break_job->close();
        $break_shift = $conn->prepare("UPDATE job_shifts SET machine_status = 'Breakdown' WHERE job_id = ? AND status = 'Running'");
        $break_shift->bind_param('i', $job_id);
        $break_shift->execute();
        $break_shift->close();
    }

    if (isset($_FILES['breakdown_photo'])) {
        $stored_photo = store_breakdown_photo($_FILES['breakdown_photo']);
        if ($stored_photo) {
            $photo = $conn->prepare("INSERT INTO maintenance_attachments
                (ticket_id, original_name, stored_name, mime_type, file_size, uploaded_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?)");
            $photo->bind_param('isssii', $ticket_id, $stored_photo['original_name'], $stored_photo['stored_name'],
                $stored_photo['mime_type'], $stored_photo['file_size'], $user_id);
            $photo->execute();
            $photo->close();
        }
    }

    audit_log($conn, $user, 'Maintenance', 'Breakdown Raised',
        'Raised ' . $breakdown_type . ' breakdown ticket ' . $ticket_no . ' for machine ID ' . $machine_id,
        'maintenance_ticket', $ticket_id);
    $conn->commit();
    json_response(['success' => true, 'ticket_id' => $ticket_id, 'ticket_no' => $ticket_no], 201);
} catch (RuntimeException $error) {
    $conn->rollback();
    if (!empty($stored_photo['path']) && is_file($stored_photo['path'])) unlink($stored_photo['path']);
    $code = $error->getCode();
    if (in_array($code, [400, 404, 409], true)) json_error($error->getMessage(), $code);
    json_server_error('Raise breakdown ticket', $error, 'Unable to raise breakdown ticket');
} catch (Throwable $error) {
    $conn->rollback();
    if (!empty($stored_photo['path']) && is_file($stored_photo['path'])) unlink($stored_photo['path']);
    json_server_error('Raise breakdown ticket', $error, 'Unable to raise breakdown ticket');
}
$conn->close();
?>
