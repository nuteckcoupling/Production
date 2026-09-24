<?php
require __DIR__ . "/bootstrap.php";
$user = require_auth();
$data = json_input();

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$week_start = trim($data['week_start'] ?? '');
$machine_id = filter_var($data['machine_id'] ?? null, FILTER_VALIDATE_INT);
$shift = trim($data['shift'] ?? '');
$part_id = filter_var($data['part_id'] ?? null, FILTER_VALIDATE_INT);
$operation = trim($data['operation'] ?? '');
$planned_qty = filter_var($data['planned_qty'] ?? null, FILTER_VALIDATE_INT);
$priority = $data['priority'] ?? 'Normal';
$status = $data['status'] ?? 'Draft';
$remarks = trim($data['remarks'] ?? '');
$shift_master = find_shift($conn, $shift);
$is_new = !$id;

$date = DateTime::createFromFormat('Y-m-d', $week_start);
if (!$date || $date->format('Y-m-d') !== $week_start) {
    json_error('Valid Week Start is required', 400);
}
if (!$machine_id || !$part_id || $operation === '' || !$planned_qty || $planned_qty < 1) {
    json_error('Machine, Part, Operation and Planned Quantity are required', 400);
}
if (!$shift_master) {
    json_error('Select a valid Shift', 400);
}
if (!in_array($priority, ['Low', 'Normal', 'High', 'Urgent'], true)) {
    json_error('Invalid priority', 400);
}
if (!in_array($status, ['Draft', 'Final'], true)) {
    json_error('Invalid plan status', 400);
}
$week_end = (clone $date)->modify('+6 days')->format('Y-m-d');

try {
    if ($id) {
        $stmt = $conn->prepare("UPDATE weekly_plans SET week_start = ?, week_end = ?, machine_id = ?, shift = ?, part_id = ?, operation = ?, planned_qty = ?, priority = ?, remarks = ?, status = ? WHERE id = ?");
        $stmt->bind_param('ssisisssssi', $week_start, $week_end, $machine_id, $shift, $part_id, $operation, $planned_qty, $priority, $remarks, $status, $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $check = $conn->prepare('SELECT id FROM weekly_plans WHERE id = ?');
            $check->bind_param('i', $id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                json_error('Weekly Plan not found', 404);
            }
            $check->close();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO weekly_plans (week_start, week_end, machine_id, shift, part_id, operation, planned_qty, priority, remarks, status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssisisssssi', $week_start, $week_end, $machine_id, $shift, $part_id, $operation, $planned_qty, $priority, $remarks, $status, $user['id']);
        $stmt->execute();
        $id = $stmt->insert_id;
        http_response_code(201);
    }
    audit_log($conn, $user, 'Production Plan', $is_new ? 'Created' : 'Updated',
        ($is_new ? 'Created' : 'Updated') . ' Weekly Plan #' . $id . ' for week ' . $week_start,
        'weekly_plan', (int)$id);
    json_response(['success' => true, 'id' => (int)$id]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        json_error('This exact Machine, Shift, Part and Operation already exists for the selected week', 409);
    }
    json_error('Unable to save Weekly Plan', 500);
}
$stmt->close();
$conn->close();
?>
