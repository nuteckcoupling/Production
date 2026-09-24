<?php
function staff_usage_count(mysqli $conn, int $staff_id): int
{
    $stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM daily_entries WHERE operator_id = ?) +
        (SELECT COUNT(*) FROM production_jobs WHERE current_operator_id = ?) +
        (SELECT COUNT(*) FROM job_shifts WHERE operator_id = ?) +
        (SELECT COUNT(*) FROM job_setting_changes WHERE operator_id = ?) AS usage_count");
    $stmt->bind_param('iiii', $staff_id, $staff_id, $staff_id, $staff_id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['usage_count'];
    $stmt->close();
    return $count;
}

function staff_has_active_work(mysqli $conn, int $staff_id): bool
{
    $stmt = $conn->prepare("SELECT 1
        WHERE EXISTS (
            SELECT 1 FROM production_jobs
            WHERE current_operator_id = ?
              AND status IN ('Running','Handover Pending','Breakdown','Stopped')
        ) OR EXISTS (
            SELECT 1 FROM job_shifts
            WHERE operator_id = ? AND status = 'Running'
        )
        LIMIT 1");
    $stmt->bind_param('ii', $staff_id, $staff_id);
    $stmt->execute();
    $has_work = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $has_work;
}

function staff_linked_user(mysqli $conn, int $staff_id): ?array
{
    $stmt = $conn->prepare("SELECT id, username, status FROM users WHERE operator_id = ? LIMIT 1");
    $stmt->bind_param('i', $staff_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $user;
}
?>
