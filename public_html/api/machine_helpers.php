<?php
function machine_usage_count(mysqli $conn, int $machine_id): int
{
    $stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM daily_entries WHERE machine_id = ?) +
        (SELECT COUNT(*) FROM production_jobs WHERE machine_id = ?) +
        (SELECT COUNT(*) FROM job_setting_changes WHERE machine_id = ?) +
        (SELECT COUNT(*) FROM weekly_plans WHERE machine_id = ?) +
        (SELECT COUNT(*) FROM monthly_plan_items WHERE machine_id = ?) AS usage_count");
    $stmt->bind_param('iiiii', $machine_id, $machine_id, $machine_id, $machine_id, $machine_id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['usage_count'];
    $stmt->close();
    return $count;
}

function machine_has_active_job(mysqli $conn, int $machine_id): bool
{
    $stmt = $conn->prepare("SELECT id FROM production_jobs
                            WHERE machine_id = ?
                              AND status IN ('Running','Handover Pending','Breakdown','Stopped')
                            LIMIT 1");
    $stmt->bind_param('i', $machine_id);
    $stmt->execute();
    $has_job = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $has_job;
}
?>
