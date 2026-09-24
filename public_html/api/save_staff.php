<?php
require __DIR__ . "/bootstrap.php";
$user = require_admin();
$data = json_input();

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$staff_code = strtoupper(trim($data['staff_code'] ?? ''));
$name = trim($data['name'] ?? '');
$designation = trim($data['designation'] ?? '');
$department = trim($data['department'] ?? '');
$phone = trim($data['phone'] ?? '');
$status = $data['status'] ?? 'Active';
$is_new = !$id;

if ($staff_code === '' || strlen($staff_code) > 30 || !preg_match('/^[A-Z0-9][A-Z0-9 _\/-]*$/', $staff_code)) {
    json_error('Staff ID must use letters, numbers, spaces, /, _ or -', 400);
}
if ($name === '' || strlen($name) > 100) json_error('Staff Name is required', 400);
if ($designation === '' || strlen($designation) > 100) json_error('Designation is required', 400);
if ($department === '' || strlen($department) > 100) json_error('Department is required', 400);
if (strlen($phone) > 20 || ($phone !== '' && !preg_match('/^[0-9+() -]+$/', $phone))) {
    json_error('Enter a valid phone number', 400);
}
if (!in_array($status, ['Active', 'Inactive'], true)) json_error('Invalid staff status', 400);
$phone = $phone === '' ? null : $phone;

try {
    if ($id) {
        $existing = $conn->prepare("SELECT id FROM operators WHERE id = ? LIMIT 1");
        $existing->bind_param('i', $id);
        $existing->execute();
        $exists = $existing->get_result()->num_rows === 1;
        $existing->close();
        if (!$exists) json_error('Staff member not found', 404);
        if ($status === 'Inactive' && staff_has_active_work($conn, $id)) {
            json_error('Staff has a running job or shift. End the work before deactivating.', 409);
        }
        $stmt = $conn->prepare("UPDATE operators
                               SET staff_code = ?, name = ?, designation = ?, department = ?, phone = ?, status = ?
                               WHERE id = ?");
        $stmt->bind_param('ssssssi', $staff_code, $name, $designation, $department, $phone, $status, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO operators
                               (staff_code, name, designation, department, phone, status)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $staff_code, $name, $designation, $department, $phone, $status);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        http_response_code(201);
    }
    audit_log($conn, $user, 'Staff Management', $is_new ? 'Created' : 'Updated',
        ($is_new ? 'Created staff ' : 'Updated staff ') . $staff_code . ' — ' . $name . ' (' . $status . ')',
        'staff', (int)$id);
    json_response(['success' => true, 'id' => (int)$id]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) json_error('Staff ID already exists', 409);
    json_error('Unable to save staff member', 500);
}
$stmt->close();
$conn->close();
?>
