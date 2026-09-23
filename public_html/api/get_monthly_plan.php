<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$month = trim($_GET['month'] ?? '');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    json_error('Valid plan month is required', 400);
}
$month_start = DateTime::createFromFormat('!Y-m-d', $month . '-01');
if (!$month_start || $month_start->format('Y-m') !== $month) {
    json_error('Invalid plan month', 400);
}
$start = $month_start->format('Y-m-d');
$end = (clone $month_start)->modify('last day of this month')->format('Y-m-d');

$source_stmt = $conn->prepare("SELECT COUNT(*) AS line_count, COALESCE(SUM(planned_qty), 0) AS planned_qty
                               FROM weekly_plans WHERE week_start BETWEEN ? AND ?");
$source_stmt->bind_param('ss', $start, $end);
$source_stmt->execute();
$source = $source_stmt->get_result()->fetch_assoc();
$source_stmt->close();
$source['line_count'] = (int)$source['line_count'];
$source['planned_qty'] = (int)$source['planned_qty'];

$plan_stmt = $conn->prepare("SELECT mp.id, mp.plan_month, mp.status, mp.created_at, mp.updated_at, mp.locked_at,
                                    COALESCE(co.name, cu.username) AS created_by,
                                    COALESCE(lo.name, lu.username) AS locked_by
                             FROM monthly_plans mp
                             JOIN users cu ON cu.id = mp.created_by_user_id
                             LEFT JOIN operators co ON co.id = cu.operator_id
                             LEFT JOIN users lu ON lu.id = mp.locked_by_user_id
                             LEFT JOIN operators lo ON lo.id = lu.operator_id
                             WHERE mp.plan_month = ? LIMIT 1");
$plan_stmt->bind_param('s', $start);
$plan_stmt->execute();
$plan = $plan_stmt->get_result()->fetch_assoc();
$plan_stmt->close();

$items = [];
if ($plan) {
    $plan['id'] = (int)$plan['id'];
    $item_stmt = $conn->prepare("SELECT mpi.id, mpi.machine_id, m.code AS machine_code, m.name AS machine_name,
                                        mpi.shift, mpi.part_id, p.part_name, mpi.operation,
                                        mpi.weekly_line_count, mpi.planned_qty
                                 FROM monthly_plan_items mpi
                                 JOIN machines m ON m.id = mpi.machine_id
                                 JOIN parts p ON p.id = mpi.part_id
                                 WHERE mpi.monthly_plan_id = ?
                                 ORDER BY m.code, mpi.shift, p.part_name, mpi.operation");
    $item_stmt->bind_param('i', $plan['id']);
    $item_stmt->execute();
    $result = $item_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['machine_id'] = (int)$row['machine_id'];
        $row['part_id'] = (int)$row['part_id'];
        $row['weekly_line_count'] = (int)$row['weekly_line_count'];
        $row['planned_qty'] = (int)$row['planned_qty'];
        $items[] = $row;
    }
    $item_stmt->close();
}

json_response([
    'month' => $month,
    'period_label' => $month_start->format('F Y'),
    'exists' => $plan !== null,
    'plan' => $plan,
    'items' => $items,
    'source' => $source,
    'generated_at' => date('c')
]);
$conn->close();
?>
