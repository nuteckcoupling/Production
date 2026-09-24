<?php
require __DIR__ . "/bootstrap.php";
require_admin();
$data = json_input();

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$code = strtoupper(trim($data['code'] ?? ''));
$name = trim($data['name'] ?? '');
$default_operation = trim($data['default_operation'] ?? '');
$status = $data['status'] ?? 'Active';

if ($code === '' || strlen($code) > 30 || !preg_match('/^[A-Z0-9][A-Z0-9 _\/-]*$/', $code)) {
    json_error('Machine Number must use letters, numbers, spaces, /, _ or -', 400);
}
if ($name === '' || strlen($name) > 100) {
    json_error('Machine Name is required', 400);
}
if ($default_operation === '' || strlen($default_operation) > 150) {
    json_error('Default Operation is required', 400);
}
if (!in_array($status, ['Active', 'Inactive'], true)) {
    json_error('Invalid machine status', 400);
}

try {
    if ($id) {
        $existing_stmt = $conn->prepare("SELECT id FROM machines WHERE id = ? LIMIT 1");
        $existing_stmt->bind_param('i', $id);
        $existing_stmt->execute();
        $exists = $existing_stmt->get_result()->num_rows === 1;
        $existing_stmt->close();
        if (!$exists) {
            json_error('Machine not found', 404);
        }
        if ($status === 'Inactive' && machine_has_active_job($conn, $id)) {
            json_error('Running machine cannot be deactivated. End the current job first.', 409);
        }
        $stmt = $conn->prepare("UPDATE machines
                               SET code = ?, name = ?, default_operation = ?, status = ?
                               WHERE id = ?");
        $stmt->bind_param('ssssi', $code, $name, $default_operation, $status, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO machines (code, name, default_operation, status)
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $code, $name, $default_operation, $status);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        http_response_code(201);
    }
    json_response(['success' => true, 'id' => (int)$id]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        json_error('Machine Number already exists', 409);
    }
    json_error('Unable to save machine', 500);
}
$stmt->close();
$conn->close();
?>
