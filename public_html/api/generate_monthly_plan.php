<?php
require __DIR__ . "/bootstrap.php";
$user = require_auth();
$data = json_input();
$month = trim($data['month'] ?? '');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    json_error('Valid plan month is required', 400);
}
$month_start = DateTime::createFromFormat('!Y-m-d', $month . '-01');
if (!$month_start || $month_start->format('Y-m') !== $month) {
    json_error('Invalid plan month', 400);
}
$start = $month_start->format('Y-m-d');
$end = (clone $month_start)->modify('last day of this month')->format('Y-m-d');

$source_stmt = $conn->prepare("SELECT COUNT(*) AS line_count FROM weekly_plans WHERE week_start BETWEEN ? AND ?");
$source_stmt->bind_param('ss', $start, $end);
$source_stmt->execute();
$source_count = (int)$source_stmt->get_result()->fetch_assoc()['line_count'];
$source_stmt->close();
if ($source_count === 0) {
    json_error('No Weekly Plan records found for the selected month', 400);
}

try {
    $conn->begin_transaction();
    $plan_stmt = $conn->prepare("SELECT id, status FROM monthly_plans WHERE plan_month = ? FOR UPDATE");
    $plan_stmt->bind_param('s', $start);
    $plan_stmt->execute();
    $plan = $plan_stmt->get_result()->fetch_assoc();
    $plan_stmt->close();

    if ($plan && $plan['status'] === 'Locked') {
        $conn->rollback();
        json_error('This Monthly Plan is locked and cannot be refreshed', 409);
    }
    if ($plan) {
        $plan_id = (int)$plan['id'];
    } else {
        $insert_plan = $conn->prepare("INSERT INTO monthly_plans (plan_month, status, created_by_user_id) VALUES (?, 'Draft', ?)");
        $insert_plan->bind_param('si', $start, $user['id']);
        $insert_plan->execute();
        $plan_id = (int)$insert_plan->insert_id;
        $insert_plan->close();
    }

    $delete_items = $conn->prepare("DELETE FROM monthly_plan_items WHERE monthly_plan_id = ?");
    $delete_items->bind_param('i', $plan_id);
    $delete_items->execute();
    $delete_items->close();

    $insert_items = $conn->prepare("INSERT INTO monthly_plan_items
        (monthly_plan_id, machine_id, shift, part_id, operation, planned_qty, weekly_line_count)
        SELECT ?, machine_id, shift, part_id, operation, SUM(planned_qty), COUNT(*)
        FROM weekly_plans
        WHERE week_start BETWEEN ? AND ?
        GROUP BY machine_id, shift, part_id, operation");
    $insert_items->bind_param('iss', $plan_id, $start, $end);
    $insert_items->execute();
    $item_count = $insert_items->affected_rows;
    $insert_items->close();

    $touch = $conn->prepare("UPDATE monthly_plans SET status = 'Draft', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $touch->bind_param('i', $plan_id);
    $touch->execute();
    $touch->close();
    $conn->commit();
    audit_log($conn, $user, 'Production Plan', 'Generated',
        'Generated Monthly Plan ' . $month . ' with ' . $item_count . ' items', 'monthly_plan', (int)$plan_id);
    json_response(['success' => true, 'id' => $plan_id, 'item_count' => $item_count]);
} catch (Throwable $error) {
    $conn->rollback();
    json_error('Unable to generate Monthly Plan', 500);
}
$conn->close();
?>
