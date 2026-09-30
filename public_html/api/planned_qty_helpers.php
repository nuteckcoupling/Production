<?php
function get_planned_qty(mysqli $conn, int $machine_id, int $part_id): int
{
    $stmt = $conn->prepare("SELECT GREATEST(
        COALESCE((SELECT MAX(de.total_qty) FROM daily_entries de
                  WHERE de.machine_id = ? AND de.part_id = ? AND de.shift_hours = 12), 0),
        COALESCE((SELECT MAX(js.total_qty) FROM job_shifts js
                  JOIN production_jobs pj ON pj.id = js.job_id
                  WHERE pj.machine_id = ? AND pj.part_id = ?
                    AND js.shift_hours = 12 AND js.status = 'Ended'), 0)
    ) AS planned_qty");
    $stmt->bind_param('iiii', $machine_id, $part_id, $machine_id, $part_id);
    $stmt->execute();
    $planned_qty = (int)$stmt->get_result()->fetch_assoc()['planned_qty'];
    $stmt->close();
    return $planned_qty;
}

function resolve_planned_qty(mysqli $conn, int $machine_id, int $part_id, mixed $manual_value): int
{
    $planned_qty = get_planned_qty($conn, $machine_id, $part_id);
    if ($planned_qty > 0) return $planned_qty;
    if (filter_var($manual_value, FILTER_VALIDATE_INT) === false || (int)$manual_value < 1) {
        throw new RuntimeException('No 12-hour production history found. Enter Planned Qty manually.', 400);
    }
    return (int)$manual_value;
}
?>
