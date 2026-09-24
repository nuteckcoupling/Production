<?php
require __DIR__ . "/bootstrap.php";
$user = require_admin();
$data = json_input();
$enabled = filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
$backup_time = trim($data['backup_time'] ?? '');
$retention_count = filter_var($data['retention_count'] ?? null, FILTER_VALIDATE_INT);
if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $backup_time)) json_error('Select a valid daily backup time', 400);
if ($retention_count === false || $retention_count < 1 || $retention_count > 90) {
    json_error('Automatic backup retention must be between 1 and 90', 400);
}
$backup_time .= ':00';
$stmt = $conn->prepare("UPDATE backup_settings
                        SET enabled = ?, backup_time = ?, retention_count = ?, updated_by_user_id = ?
                        WHERE id = 1");
$stmt->bind_param('isii', $enabled, $backup_time, $retention_count, $user['id']);
$stmt->execute();
$stmt->close();
audit_log($conn, $user, 'Backup Management', 'Schedule Updated',
    'Automatic backup ' . ($enabled ? 'enabled' : 'disabled') . ' at ' . substr($backup_time, 0, 5) .
    '; keep latest ' . $retention_count . ' automatic backups', 'backup_schedule', 1);
json_response(['success' => true]);
$conn->close();
?>
