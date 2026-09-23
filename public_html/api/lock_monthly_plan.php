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

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT mp.id, mp.status, COUNT(mpi.id) AS item_count
                            FROM monthly_plans mp
                            LEFT JOIN monthly_plan_items mpi ON mpi.monthly_plan_id = mp.id
                            WHERE mp.plan_month = ?
                            GROUP BY mp.id, mp.status
                            FOR UPDATE");
    $stmt->bind_param('s', $start);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$plan) {
        $conn->rollback();
        json_error('Generate the Monthly Plan before locking it', 404);
    }
    if ($plan['status'] === 'Locked') {
        $conn->rollback();
        json_error('This Monthly Plan is already locked', 409);
    }
    if ((int)$plan['item_count'] === 0) {
        $conn->rollback();
        json_error('Monthly Plan has no items to lock', 400);
    }
    $update = $conn->prepare("UPDATE monthly_plans
                             SET status = 'Locked', locked_by_user_id = ?, locked_at = NOW()
                             WHERE id = ?");
    $update->bind_param('ii', $user['id'], $plan['id']);
    $update->execute();
    $update->close();
    $conn->commit();
    json_response(['success' => true, 'id' => (int)$plan['id']]);
} catch (Throwable $error) {
    $conn->rollback();
    json_error('Unable to lock Monthly Plan', 500);
}
$conn->close();
?>
