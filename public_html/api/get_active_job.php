<?php
require __DIR__ . "/bootstrap.php";
$user = require_auth();

$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$job_id) {
    http_response_code(400);
    json_response(['error' => 'Valid job_id is required']);
    exit;
}

$stmt = $conn->prepare("SELECT
        j.id, j.status, j.machine_id, m.code AS machine_code, m.name AS machine_name,
        m.default_operation AS machine_default_operation,
        j.part_id, p.coupling_type, p.part_name, j.component, j.drg_no, j.operation,
        j.cutter_id, c.cutter_num, j.planned_qty, j.cumulative_ok_qty,
        GREATEST(j.planned_qty - j.cumulative_ok_qty, 0) AS pending_qty,
        j.current_operator_id, o.name AS operator_name, j.current_shift,
        j.started_at, j.status_changed_at, j.completed_at, j.remarks,
        s.id AS shift_id, s.shift_date, s.shift, s.shift_hours, s.started_at AS shift_started_at,
        last_s.id AS last_shift_id, last_s.shift AS last_shift, last_s.shift_hours AS last_shift_hours,
        last_s.started_at AS last_shift_started_at, last_s.ended_at AS last_shift_ended_at,
        last_s.total_qty AS last_total_qty, last_s.ok_qty AS last_ok_qty,
        last_s.mc_reject_qty AS last_mc_reject_qty, last_s.rm_defect_qty AS last_rm_defect_qty,
        last_s.rework_qty AS last_rework_qty, last_s.downtime_min AS last_downtime_min,
        last_s.issue_code AS last_issue_code, last_s.remarks AS last_remarks,
        last_o.name AS last_operator_name,
        cc.id AS cutter_change_id, cc.old_cutter_id, old_c.cutter_num AS old_cutter_num,
        cc.new_cutter_id, new_c.cutter_num AS new_cutter_num, cc.reason AS cutter_change_reason,
        cc.remarks AS cutter_change_remarks, cc.started_at AS cutter_change_started_at,
        TIMESTAMPDIFF(MINUTE, cc.started_at, NOW()) AS cutter_change_elapsed_minutes,
        sc.id AS setting_change_id, sc.new_part_id AS setting_new_part_id,
        setting_p.coupling_type AS setting_new_coupling_type, setting_p.part_name AS setting_new_part_name,
        sc.new_component AS setting_new_component, sc.new_drg_no AS setting_new_drg_no,
        sc.new_operation AS setting_new_operation, sc.new_cutter_id AS setting_new_cutter_id,
        setting_c.cutter_num AS setting_new_cutter_num, sc.new_planned_qty AS setting_new_planned_qty,
        sc.reason AS setting_change_reason, sc.remarks AS setting_change_remarks,
        sc.total_qty AS setting_total_qty, sc.ok_qty AS setting_ok_qty, sc.mc_reject_qty AS setting_mc_reject_qty,
        sc.rm_defect_qty AS setting_rm_defect_qty, sc.rework_qty AS setting_rework_qty,
        sc.started_at AS setting_change_started_at,
        TIMESTAMPDIFF(MINUTE, sc.started_at, NOW()) AS setting_change_elapsed_minutes
    FROM production_jobs j
    JOIN machines m ON m.id = j.machine_id
    JOIN parts p ON p.id = j.part_id
    LEFT JOIN cutters c ON c.id = j.cutter_id
    LEFT JOIN operators o ON o.id = j.current_operator_id
    LEFT JOIN job_shifts s ON s.job_id = j.id AND s.status = 'Running'
    LEFT JOIN job_shifts last_s ON last_s.id = (
        SELECT MAX(previous_s.id) FROM job_shifts previous_s
        WHERE previous_s.job_id = j.id AND previous_s.status = 'Ended'
    )
    LEFT JOIN operators last_o ON last_o.id = last_s.operator_id
    LEFT JOIN job_cutter_changes cc ON cc.job_id = j.id AND cc.status = 'In Progress'
    LEFT JOIN cutters old_c ON old_c.id = cc.old_cutter_id
    LEFT JOIN cutters new_c ON new_c.id = cc.new_cutter_id
    LEFT JOIN job_setting_changes sc ON sc.old_job_id = j.id AND sc.status = 'In Progress'
    LEFT JOIN parts setting_p ON setting_p.id = sc.new_part_id
    LEFT JOIN cutters setting_c ON setting_c.id = sc.new_cutter_id
    WHERE j.id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$job) {
    http_response_code(404);
    json_response(['error' => 'Job not found']);
    exit;
}

foreach (['id', 'machine_id', 'part_id', 'planned_qty', 'cumulative_ok_qty', 'pending_qty', 'current_operator_id'] as $field) {
    $job[$field] = $job[$field] === null ? null : (int)$job[$field];
}
$job['cutter_id'] = $job['cutter_id'] === null ? null : (int)$job['cutter_id'];
$job['shift_id'] = $job['shift_id'] === null ? null : (int)$job['shift_id'];
$job['shift_hours'] = $job['shift_hours'] === null ? null : (float)$job['shift_hours'];
foreach (['last_shift_id', 'last_total_qty', 'last_ok_qty', 'last_mc_reject_qty',
    'last_rm_defect_qty', 'last_rework_qty', 'last_downtime_min'] as $field) {
    $job[$field] = $job[$field] === null ? null : (int)$job[$field];
}
$job['cutter_change_id'] = $job['cutter_change_id'] === null ? null : (int)$job['cutter_change_id'];
$job['last_shift_hours'] = $job['last_shift_hours'] === null ? null : (float)$job['last_shift_hours'];
$job['old_cutter_id'] = $job['old_cutter_id'] === null ? null : (int)$job['old_cutter_id'];
$job['new_cutter_id'] = $job['new_cutter_id'] === null ? null : (int)$job['new_cutter_id'];
$job['cutter_change_elapsed_minutes'] = $job['cutter_change_elapsed_minutes'] === null ? null : (int)$job['cutter_change_elapsed_minutes'];
$job['setting_change_id'] = $job['setting_change_id'] === null ? null : (int)$job['setting_change_id'];
foreach (['setting_new_part_id', 'setting_new_cutter_id', 'setting_new_planned_qty', 'setting_total_qty',
    'setting_ok_qty', 'setting_mc_reject_qty', 'setting_rm_defect_qty', 'setting_rework_qty',
    'setting_change_elapsed_minutes'] as $field) {
    $job[$field] = $job[$field] === null ? null : (int)$job[$field];
}
$job['can_end_shift'] = ($user['role'] ?? '') === 'Operator/Supervisor'
    && (int)($user['operator_id'] ?? 0) === (int)$job['current_operator_id']
    && $job['status'] === 'Running'
    && $job['shift_id'] !== null
    && $job['cutter_change_id'] === null
    && $job['setting_change_id'] === null;
$job['can_start_cutter_change'] = ($user['role'] ?? '') === 'Operator/Supervisor'
    && (int)($user['operator_id'] ?? 0) === (int)$job['current_operator_id']
    && $job['status'] === 'Running' && $job['shift_id'] !== null
    && $job['cutter_change_id'] === null
    && $job['setting_change_id'] === null;
$job['can_complete_cutter_change'] = ($user['role'] ?? '') === 'Operator/Supervisor'
    && (int)($user['operator_id'] ?? 0) === (int)$job['current_operator_id']
    && $job['status'] === 'Running' && $job['shift_id'] !== null
    && $job['cutter_change_id'] !== null
    && $job['setting_change_id'] === null;
$job['can_start_setting_change'] = ($user['role'] ?? '') === 'Operator/Supervisor'
    && (int)($user['operator_id'] ?? 0) === (int)$job['current_operator_id']
    && $job['status'] === 'Running' && $job['shift_id'] !== null
    && $job['cutter_change_id'] === null
    && $job['setting_change_id'] === null;
$job['can_complete_setting_change'] = ($user['role'] ?? '') === 'Operator/Supervisor'
    && (int)($user['operator_id'] ?? 0) === (int)$job['current_operator_id']
    && $job['status'] === 'Running' && $job['shift_id'] !== null
    && $job['cutter_change_id'] === null
    && $job['setting_change_id'] !== null;
$job['can_accept_handover'] = ($user['role'] ?? '') === 'Operator/Supervisor'
    && (int)($user['operator_id'] ?? 0) !== (int)$job['current_operator_id']
    && $job['status'] === 'Handover Pending'
    && $job['shift_id'] === null;

json_response($job);
$conn->close();
?>
