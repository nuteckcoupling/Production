<?php
require __DIR__ . '/bootstrap.php';
$user = require_auth();
$data = json_input();
$ticket_id = filter_var($data['ticket_id'] ?? null, FILTER_VALIDATE_INT);
$action = trim((string)($data['action'] ?? ''));
if (!$ticket_id || !in_array($action, ['assign','start_work','complete_repair','confirm_running'], true)) {
    json_error('Valid maintenance ticket action is required', 400);
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT * FROM maintenance_tickets WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$ticket) throw new RuntimeException('Maintenance ticket not found', 404);
    if ($ticket['status'] === 'Closed') throw new RuntimeException('Maintenance ticket is already closed', 409);

    $user_id = (int)$user['id'];
    $operator_id = (int)($user['operator_id'] ?? 0);
    $audit_action = '';
    $audit_detail = '';

    if ($action === 'assign') {
        if (($user['role'] ?? '') !== 'Maintenance') throw new RuntimeException('Only Maintenance can assign a maintenance person', 403);
        if (!in_array($ticket['status'], ['Open','Assigned'], true)) throw new RuntimeException('Work has already started on this ticket', 409);
        $staff_id = filter_var($data['assigned_staff_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$staff_id) throw new RuntimeException('Select a maintenance person', 400);
        $staff_stmt = $conn->prepare("SELECT o.id FROM operators o
            JOIN users u ON u.operator_id = o.id AND u.status = 'Active' AND u.role = 'Maintenance'
            WHERE o.id = ? AND o.status = 'Active' AND LOWER(TRIM(o.department)) = 'maintenance'");
        $staff_stmt->bind_param('i', $staff_id);
        $staff_stmt->execute();
        $staff = $staff_stmt->get_result()->fetch_assoc();
        $staff_stmt->close();
        if (!$staff) throw new RuntimeException('Selected maintenance person needs an active Maintenance login', 400);
        $update = $conn->prepare("UPDATE maintenance_tickets SET assigned_staff_id = ?, assigned_by_user_id = ?, assigned_at = NOW(), status = 'Assigned' WHERE id = ?");
        $update->bind_param('iii', $staff_id, $user_id, $ticket_id);
        $audit_action = 'Maintenance Assigned';
        $audit_detail = 'Assigned staff ID ' . $staff_id;
    } elseif ($action === 'start_work') {
        if (($user['role'] ?? '') !== 'Maintenance' || !$operator_id) throw new RuntimeException('Maintenance login required', 403);
        if (!in_array($ticket['status'], ['Open','Assigned'], true)) throw new RuntimeException('Maintenance work cannot be started from the current status', 409);
        if ($ticket['assigned_staff_id'] !== null && (int)$ticket['assigned_staff_id'] !== $operator_id) {
            throw new RuntimeException('This ticket is assigned to another maintenance person', 403);
        }
        $update = $conn->prepare("UPDATE maintenance_tickets SET assigned_staff_id = ?, assigned_by_user_id = COALESCE(assigned_by_user_id, ?),
            assigned_at = COALESCE(assigned_at, NOW()), work_started_at = NOW(), status = 'In Progress' WHERE id = ?");
        $update->bind_param('iii', $operator_id, $user_id, $ticket_id);
        $audit_action = 'Maintenance Started';
        $audit_detail = 'Work started by staff ID ' . $operator_id;
    } elseif ($action === 'complete_repair') {
        if (($user['role'] ?? '') !== 'Maintenance' || !$operator_id) throw new RuntimeException('Maintenance login required', 403);
        if ($ticket['status'] !== 'In Progress' || (int)$ticket['assigned_staff_id'] !== $operator_id) {
            throw new RuntimeException('Only the assigned maintenance person can complete this repair', 403);
        }
        $repair_details = trim((string)($data['repair_details'] ?? ''));
        $spare_parts = trim((string)($data['spare_parts_used'] ?? ''));
        if ($repair_details === '' || strlen($repair_details) > 1000 || strlen($spare_parts) > 500) {
            throw new RuntimeException('Repair details are required; repair and spare-parts text is too long', 400);
        }
        $update = $conn->prepare("UPDATE maintenance_tickets SET repair_details = ?, spare_parts_used = NULLIF(?, ''),
            repair_completed_at = NOW(), status = 'Repair Completed' WHERE id = ?");
        $update->bind_param('ssi', $repair_details, $spare_parts, $ticket_id);
        $audit_action = 'Repair Completed';
        $audit_detail = 'Repair completed by staff ID ' . $operator_id;
    } else {
        if (($user['role'] ?? '') !== 'Operator/Supervisor') throw new RuntimeException('Operator/Supervisor confirmation required', 403);
        if ($ticket['status'] !== 'Repair Completed') throw new RuntimeException('Repair must be completed before machine testing', 409);
        $testing_remarks = trim((string)($data['testing_remarks'] ?? ''));
        if ($testing_remarks === '' || strlen($testing_remarks) > 500) throw new RuntimeException('Testing remarks are required and must be 500 characters or fewer', 400);
        $update = $conn->prepare("UPDATE maintenance_tickets SET testing_remarks = ?, machine_running_confirmed_at = NOW(),
            closed_by_user_id = ?, status = 'Closed' WHERE id = ?");
        $update->bind_param('sii', $testing_remarks, $user_id, $ticket_id);
        $audit_action = 'Machine Running Confirmed';
        $audit_detail = 'Operator testing completed';
    }
    $update->execute();
    $update->close();

    if ($action === 'confirm_running' && $ticket['job_id'] !== null) {
        $job_id = (int)$ticket['job_id'];
        $restore_job = $conn->prepare("UPDATE production_jobs SET status = 'Running', status_changed_at = NOW()
            WHERE id = ? AND status = 'Breakdown'");
        $restore_job->bind_param('i', $job_id);
        $restore_job->execute();
        if ($restore_job->affected_rows !== 1) throw new RuntimeException('Linked production job is not ready to resume', 409);
        $restore_job->close();
        $breakdown_started_at = $ticket['breakdown_started_at'];
        $restore_shift = $conn->prepare("UPDATE job_shifts SET machine_status = 'Running',
            downtime_min = downtime_min + GREATEST(TIMESTAMPDIFF(MINUTE, ?, NOW()), 0)
            WHERE job_id = ? AND status = 'Running'");
        $restore_shift->bind_param('si', $breakdown_started_at, $job_id);
        $restore_shift->execute();
        $restore_shift->close();
    }

    audit_log($conn, $user, 'Maintenance', $audit_action,
        $audit_detail . ' for ticket ' . ($ticket['ticket_no'] ?: '#' . $ticket_id), 'maintenance_ticket', $ticket_id);
    $conn->commit();
    json_response(['success' => true, 'ticket_id' => $ticket_id, 'action' => $action]);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    json_error($error->getMessage(), in_array($code, [400,403,404,409], true) ? $code : 400);
} catch (Throwable $error) {
    $conn->rollback();
    json_server_error('Update maintenance ticket', $error, 'Unable to update maintenance ticket');
}
$conn->close();
?>
