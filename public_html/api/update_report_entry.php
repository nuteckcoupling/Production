<?php
require __DIR__ . '/bootstrap.php';
$user = require_auth();
if (!in_array($user['role'] ?? '', ['Admin', 'Operator/Supervisor'], true)) {
    json_error('Production report edit access required', 403);
}

$data = json_input();
$shift_id = filter_var($data['shift_id'] ?? null, FILTER_VALIDATE_INT);
$period = trim((string)($data['period'] ?? ''));
$report_date = trim((string)($data['report_date'] ?? ''));
$reason = trim((string)($data['edit_reason'] ?? ''));
$remarks = trim((string)($data['remarks'] ?? ''));
$allowed_issues = ['', 'No Operator', 'No Material', 'M/C Breakdown (Mechanical)',
    'M/C Breakdown (Electrical)', 'No Power', 'New Setting', 'Other'];
$issue_code = trim((string)($data['issue_code'] ?? ''));
if (!$shift_id || $period !== 'daily' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $report_date)
    || $reason === '' || strlen($reason) > 255 || strlen($remarks) > 255
    || !in_array($issue_code, $allowed_issues, true)) {
    json_error('Valid entry, correction reason and production details are required', 400);
}

$values = [];
foreach (['total_qty', 'ok_qty', 'mc_reject_qty', 'rm_defect_qty', 'rework_qty', 'downtime_min'] as $field) {
    $raw = $data[$field] ?? null;
    if (filter_var($raw, FILTER_VALIDATE_INT) === false || (int)$raw < 0) {
        json_error('Quantities and downtime must be whole numbers of zero or more', 400);
    }
    $values[$field] = (int)$raw;
}
if ($values['ok_qty'] + $values['mc_reject_qty'] + $values['rm_defect_qty'] + $values['rework_qty'] > $values['total_qty']) {
    json_error('OK + Reject + Defect + Rework cannot exceed Total Qty', 400);
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT id, job_id, operator_id, shift_date, status, total_qty, ok_qty,
        mc_reject_qty, rm_defect_qty, rework_qty, downtime_min, issue_code, remarks, report_edited_at
        FROM job_shifts WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $shift_id);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$entry) throw new RuntimeException('Production entry not found', 404);
    if ($entry['shift_date'] !== $report_date) {
        throw new RuntimeException('Open the matching daily report date to correct this entry', 403);
    }
    if ($entry['status'] !== 'Ended') throw new RuntimeException('Only an ended shift can be corrected', 409);
    if ($entry['report_edited_at'] !== null) throw new RuntimeException('This entry has already been edited once', 409);
    if (($user['role'] ?? '') !== 'Admin') {
        $nextShift = $conn->prepare('SELECT id FROM job_shifts WHERE job_id = ? AND id > ? LIMIT 1');
        $nextShift->bind_param('ii', $entry['job_id'], $shift_id);
        $nextShift->execute();
        $hasNextShift = (bool)$nextShift->get_result()->fetch_assoc();
        $nextShift->close();
        if ($hasNextShift) {
            throw new RuntimeException('The next shift has already taken over. Ask Admin to correct this entry.', 403);
        }
        if ((int)$entry['operator_id'] !== (int)($user['operator_id'] ?? 0)) {
            throw new RuntimeException('You can edit only your own production entry', 403);
        }
    }

    $original = json_encode([
        'total_qty' => (int)$entry['total_qty'], 'ok_qty' => (int)$entry['ok_qty'],
        'mc_reject_qty' => (int)$entry['mc_reject_qty'],
        'rm_defect_qty' => (int)$entry['rm_defect_qty'], 'rework_qty' => (int)$entry['rework_qty'],
        'downtime_min' => (int)$entry['downtime_min'], 'issue_code' => $entry['issue_code'],
        'remarks' => $entry['remarks']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $issue_code = $issue_code === '' ? null : $issue_code;
    $remarks = $remarks === '' ? null : $remarks;
    $user_id = (int)$user['id'];

    $update = $conn->prepare("UPDATE job_shifts SET total_qty = ?, ok_qty = ?, mc_reject_qty = ?,
        rm_defect_qty = ?, rework_qty = ?, downtime_min = ?, issue_code = ?, remarks = ?,
        report_edited_at = NOW(), report_edited_by_user_id = ?, report_edit_reason = ?,
        report_original_data = ? WHERE id = ? AND report_edited_at IS NULL");
    $update->bind_param('iiiiiississi', $values['total_qty'], $values['ok_qty'],
        $values['mc_reject_qty'], $values['rm_defect_qty'], $values['rework_qty'],
        $values['downtime_min'], $issue_code, $remarks, $user_id, $reason, $original, $shift_id);
    $update->execute();
    if ($update->affected_rows !== 1) throw new RuntimeException('This entry has already been edited once', 409);
    $update->close();

    $ok_delta = $values['ok_qty'] - (int)$entry['ok_qty'];
    $job_id = (int)$entry['job_id'];
    $job = $conn->prepare('UPDATE production_jobs SET cumulative_ok_qty = GREATEST(cumulative_ok_qty + ?, 0) WHERE id = ?');
    $job->bind_param('ii', $ok_delta, $job_id);
    $job->execute();
    $job->close();

    $conn->commit();
    audit_log($conn, $user, 'Production Report', 'Entry Edited Once',
        'Corrected shift entry #' . $shift_id . ' for Job #' . $job_id . '; reason: ' . $reason .
        '; total ' . $entry['total_qty'] . ' to ' . $values['total_qty'] .
        '; OK ' . $entry['ok_qty'] . ' to ' . $values['ok_qty'], 'job_shift', (int)$shift_id);
    json_response(['success' => true, 'shift_id' => (int)$shift_id, 'edited_once' => true]);
} catch (RuntimeException $error) {
    $conn->rollback();
    $code = $error->getCode();
    json_error($error->getMessage(), in_array($code, [403, 404, 409], true) ? $code : 400);
} catch (Throwable $error) {
    $conn->rollback();
    json_server_error('Edit production report entry', $error, 'Unable to correct production entry');
}
$conn->close();
?>
