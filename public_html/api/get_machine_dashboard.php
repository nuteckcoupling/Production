<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$sql = "SELECT
            m.id,
            m.code,
            m.name,
            CASE
              WHEN cc.id IS NOT NULL THEN 'Cutter Change'
              WHEN sc.id IS NOT NULL THEN 'Setting Change'
              ELSE COALESCE(j.status, 'Available')
            END AS runtime_status,
            j.id AS job_id,
            COALESCE(sm.name, j.current_shift) AS current_shift,
            j.component,
            j.operation,
            j.planned_qty,
            j.cumulative_ok_qty,
            GREATEST(j.planned_qty - j.cumulative_ok_qty, 0) AS pending_qty,
            j.started_at,
            j.status_changed_at,
            j.current_operator_id,
            EXISTS(
              SELECT 1 FROM job_shifts takeover_s
              WHERE takeover_s.job_id = j.id AND takeover_s.status = 'Running'
                AND takeover_s.remarks LIKE '[TAKEOVER]%'
            ) AS takeover_pending,
            o.name AS operator_name,
            p.part_name,
            c.cutter_num,
            cc.id AS cutter_change_id,
            sc.id AS setting_change_id
        FROM machines m
        LEFT JOIN production_jobs j ON j.machine_id = m.id
         AND j.status IN ('Running', 'Handover Pending', 'Breakdown', 'Stopped')
        LEFT JOIN shifts sm ON sm.code = j.current_shift
        LEFT JOIN operators o ON o.id = j.current_operator_id
        LEFT JOIN parts p ON p.id = j.part_id
        LEFT JOIN cutters c ON c.id = j.cutter_id
        LEFT JOIN job_setting_changes sc ON sc.old_job_id = j.id AND sc.status = 'In Progress'
        LEFT JOIN job_cutter_changes cc ON cc.job_id = j.id AND cc.status = 'In Progress'
        WHERE m.status = 'Active'
        ORDER BY m.code";

$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['job_id'] = $row['job_id'] === null ? null : (int)$row['job_id'];
    $row['planned_qty'] = $row['planned_qty'] === null ? 0 : (int)$row['planned_qty'];
    $row['cumulative_ok_qty'] = $row['cumulative_ok_qty'] === null ? 0 : (int)$row['cumulative_ok_qty'];
    $row['pending_qty'] = $row['pending_qty'] === null ? 0 : (int)$row['pending_qty'];
    $row['current_operator_id'] = $row['current_operator_id'] === null ? null : (int)$row['current_operator_id'];
    $row['takeover_pending'] = (bool)$row['takeover_pending'];
    $row['setting_change_id'] = $row['setting_change_id'] === null ? null : (int)$row['setting_change_id'];
    $row['cutter_change_id'] = $row['cutter_change_id'] === null ? null : (int)$row['cutter_change_id'];
    $rows[] = $row;
}

json_response($rows);
$conn->close();
?>
