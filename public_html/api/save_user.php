<?php
require __DIR__ . "/bootstrap.php";
$current = require_admin();
$data = json_input();

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$username = strtolower(trim($data['username'] ?? ''));
$role = $data['role'] ?? '';
$operator_id = filter_var($data['operator_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$status = $data['status'] ?? 'Active';
$password = (string)($data['password'] ?? '');
$is_new = !$id;

if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,49}$/', $username)) {
    json_error('Username must be 3-50 characters using lowercase letters, numbers, ., _ or -', 400);
}
if (!in_array($role, ['Admin', 'Operator/Supervisor'], true)) json_error('Invalid user role', 400);
if (!in_array($status, ['Active', 'Inactive'], true)) json_error('Invalid user status', 400);
if ($role === 'Operator/Supervisor' && !$operator_id) json_error('Select a staff member for Operator/Supervisor', 400);
if ($role === 'Admin') $operator_id = null;
if ((!$id || $password !== '') && strlen($password) < 8) json_error('Password must be at least 8 characters', 400);
if (strlen($password) > 128) json_error('Password is too long', 400);

if ($operator_id) {
    $staff = $conn->prepare("SELECT status FROM operators WHERE id = ? LIMIT 1");
    $staff->bind_param('i', $operator_id);
    $staff->execute();
    $staff_row = $staff->get_result()->fetch_assoc();
    $staff->close();
    if (!$staff_row) json_error('Staff member not found', 404);
    if ($status === 'Active' && $staff_row['status'] !== 'Active') {
        json_error('Inactive staff cannot have an active login', 409);
    }
}

if ($id) {
    $existing_stmt = $conn->prepare("SELECT id, role, operator_id, status FROM users WHERE id = ? LIMIT 1");
    $existing_stmt->bind_param('i', $id);
    $existing_stmt->execute();
    $existing = $existing_stmt->get_result()->fetch_assoc();
    $existing_stmt->close();
    if (!$existing) json_error('User not found', 404);
    $existing_operator_id = $existing['operator_id'] === null ? null : (int)$existing['operator_id'];
    $operator_link_changes = $existing_operator_id && ($role === 'Admin' || $operator_id !== $existing_operator_id);
    if ($existing_operator_id && ($status === 'Inactive' || $operator_link_changes) && staff_has_active_work($conn, $existing_operator_id)) {
        json_error('This user has a running job or shift. End or hand over the work first.', 409);
    }
    if ($id === (int)$current['id'] && ($status !== 'Active' || $role !== 'Admin')) {
        json_error('You cannot deactivate or remove Admin access from your own account', 409);
    }
    if ($existing['role'] === 'Admin' && $existing['status'] === 'Active' && ($role !== 'Admin' || $status !== 'Active')) {
        $admin_count = (int)$conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'Admin' AND status = 'Active'")->fetch_assoc()['total'];
        if ($admin_count <= 1) json_error('At least one active Admin account is required', 409);
    }
}

try {
    if ($id) {
        if ($password !== '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users
                                   SET username = ?, role = ?, operator_id = ?, status = ?, password_hash = ?
                                   WHERE id = ?");
            $stmt->bind_param('ssissi', $username, $role, $operator_id, $status, $password_hash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, operator_id = ?, status = ? WHERE id = ?");
            $stmt->bind_param('ssisi', $username, $role, $operator_id, $status, $id);
        }
        $stmt->execute();
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, operator_id, status)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssis', $username, $password_hash, $role, $operator_id, $status);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        http_response_code(201);
    }
    audit_log($conn, $current, 'User Management', $is_new ? 'Created' : 'Updated',
        ($is_new ? 'Created user ' : 'Updated user ') . $username . ' (' . $role . ', ' . $status . ')',
        'user', (int)$id);
    if (!$is_new && $password !== '') {
        audit_log($conn, $current, 'User Management', 'Password Reset',
            'Admin reset password for user ' . $username, 'user', (int)$id);
    }
    json_response(['success' => true, 'id' => (int)$id]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        $username_check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
        $check_id = $id ?: 0;
        $username_check->bind_param('si', $username, $check_id);
        $username_check->execute();
        $duplicate_username = $username_check->get_result()->num_rows > 0;
        $username_check->close();
        json_error($duplicate_username ? 'Username already exists' : 'Selected staff already has a login account', 409);
    }
    json_error('Unable to save user', 500);
}
$stmt->close();
$conn->close();
?>
