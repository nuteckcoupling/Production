<?php
require __DIR__ . "/bootstrap.php";
require_admin();
$data = json_input();

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$code = trim($data['code'] ?? '');
$name = trim($data['name'] ?? '');
$start_time = trim($data['start_time'] ?? '');
$end_time = trim($data['end_time'] ?? '');
$status = $data['status'] ?? 'Active';
$hours = calculate_shift_hours($start_time, $end_time);

if ($code === '' || strlen($code) > 30 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9 _\\/-]*$/', $code)) {
    json_error('Shift Code must use letters, numbers, spaces, /, _ or -', 400);
}
if ($name === '' || strlen($name) > 100) {
    json_error('Shift Name is required', 400);
}
if ($hours === null) {
    json_error('Valid and different Start and End Time are required', 400);
}
if (!in_array($status, ['Active', 'Inactive'], true)) {
    json_error('Invalid shift status', 400);
}

try {
    if ($id) {
        $existing_stmt = $conn->prepare("SELECT code FROM shifts WHERE id = ? LIMIT 1");
        $existing_stmt->bind_param('i', $id);
        $existing_stmt->execute();
        $existing = $existing_stmt->get_result()->fetch_assoc();
        $existing_stmt->close();
        if (!$existing) {
            json_error('Shift not found', 404);
        }
        if ($existing['code'] !== $code && shift_usage_count($conn, $existing['code']) > 0) {
            json_error('Used Shift Code cannot be changed. Edit its name/time or deactivate it.', 409);
        }
        $stmt = $conn->prepare("UPDATE shifts SET code = ?, name = ?, start_time = ?, end_time = ?,
                               shift_hours = ?, status = ? WHERE id = ?");
        $stmt->bind_param('ssssdsi', $code, $name, $start_time, $end_time, $hours, $status, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO shifts (code, name, start_time, end_time, shift_hours, status)
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssds', $code, $name, $start_time, $end_time, $hours, $status);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        http_response_code(201);
    }
    json_response(['success' => true, 'id' => (int)$id, 'shift_hours' => $hours]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        json_error('Shift Code already exists', 409);
    }
    json_error('Unable to save shift', 500);
}
$stmt->close();
$conn->close();
?>
