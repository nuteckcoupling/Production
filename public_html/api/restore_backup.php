<?php
require __DIR__ . "/bootstrap.php";
$user = require_admin();
$data = json_input();
$filename = trim($data['file'] ?? '');
$password = (string)($data['password'] ?? '');
$confirmation = trim($data['confirmation'] ?? '');
if ($confirmation !== 'RESTORE') json_error('Type RESTORE to confirm database replacement', 400);
if ($password === '') json_error('Admin password is required', 400);

$password_stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ? AND status = 'Active' LIMIT 1");
$password_stmt->bind_param('i', $user['id']);
$password_stmt->execute();
$password_row = $password_stmt->get_result()->fetch_assoc();
$password_stmt->close();
if (!$password_row || !password_verify($password, $password_row['password_hash'])) {
    json_error('Admin password is incorrect', 401);
}

try {
    $restore_path = backup_file_path($filename);
    if (!is_file($restore_path)) json_error('Backup file not found', 404);
    $safety = create_database_backup($conn, 'safety');
    restore_database_backup($conn, $filename);
    audit_log($conn, $user, 'Backup Management', 'Restored',
        'Restored database from ' . $filename . '; safety backup: ' . $safety['filename'], 'backup', null);
    json_response(['success' => true, 'safety_backup' => $safety['filename']]);
} catch (RuntimeException $error) {
    json_error($error->getMessage(), in_array($error->getCode(), [400, 404], true) ? $error->getCode() : 500);
} catch (Throwable $error) {
    json_error('Unable to restore backup: ' . $error->getMessage(), 500);
}
$conn->close();
?>
