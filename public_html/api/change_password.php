<?php
require __DIR__ . "/bootstrap.php";
$current = require_auth();
$data = json_input();
$current_password = (string)($data['current_password'] ?? '');
$new_password = (string)($data['new_password'] ?? '');

if ($current_password === '') json_error('Current password is required', 400);
if (strlen($new_password) < 8) json_error('New password must be at least 8 characters', 400);
if (strlen($new_password) > 128) json_error('New password is too long', 400);
if ($current_password === $new_password) json_error('New password must be different from current password', 400);

$stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $current['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row || !password_verify($current_password, $row['password_hash'])) {
    json_error('Current password is incorrect', 401);
}

$password_hash = password_hash($new_password, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$update->bind_param('si', $password_hash, $current['id']);
$update->execute();
$update->close();
session_regenerate_id(true);
audit_log($conn, $current, 'User Management', 'Password Changed', 'User changed own password', 'user', (int)$current['id']);
json_response(['success' => true]);
$conn->close();
?>
