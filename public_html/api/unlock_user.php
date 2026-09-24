<?php
require __DIR__ . "/bootstrap.php";
$current = require_admin();
$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) json_error('Valid user is required', 400);

$stmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) json_error('User not found', 404);

$update = $conn->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
$update->bind_param('i', $id);
$update->execute();
$update->close();
audit_log($conn, $current, 'User Management', 'Account Unlocked',
    'Cleared failed login lock for user ' . $user['username'], 'user', (int)$id);
json_response(['success' => true]);
$conn->close();
?>
