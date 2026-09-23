<?php
function find_shift(mysqli $conn, string $code, bool $active_only = true): ?array
{
    $sql = "SELECT id, code, name, TIME_FORMAT(start_time, '%H:%i') AS start_time,
                   TIME_FORMAT(end_time, '%H:%i') AS end_time, shift_hours, status
            FROM shifts WHERE code = ?";
    if ($active_only) {
        $sql .= " AND status = 'Active'";
    }
    $sql .= " LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $shift = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $shift;
}

function calculate_shift_hours(string $start_time, string $end_time): ?float
{
    if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
        return null;
    }
    $start = DateTime::createFromFormat('!H:i', $start_time);
    $end = DateTime::createFromFormat('!H:i', $end_time);
    if (!$start || !$end || $start->format('H:i') !== $start_time || $end->format('H:i') !== $end_time || $start_time === $end_time) {
        return null;
    }
    if ($end <= $start) {
        $end->modify('+1 day');
    }
    return round(($end->getTimestamp() - $start->getTimestamp()) / 3600, 2);
}

function shift_usage_count(mysqli $conn, string $code): int
{
    $stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM daily_entries WHERE shift = ?) +
        (SELECT COUNT(*) FROM job_shifts WHERE shift = ?) +
        (SELECT COUNT(*) FROM production_jobs WHERE current_shift = ?) +
        (SELECT COUNT(*) FROM weekly_plans WHERE shift = ?) +
        (SELECT COUNT(*) FROM monthly_plan_items WHERE shift = ?) AS usage_count");
    $stmt->bind_param('sssss', $code, $code, $code, $code, $code);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['usage_count'];
    $stmt->close();
    return $count;
}
?>
