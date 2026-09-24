<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$lock_result = $conn->query("SELECT GET_LOCK('nuteck_production_auto_backup', 0) AS acquired")->fetch_assoc();
if ((int)($lock_result['acquired'] ?? 0) !== 1) {
    json_response(['success' => true, 'performed' => false, 'reason' => 'busy']);
    $conn->close();
    exit;
}

try {
    $settings = $conn->query("SELECT enabled, backup_time, retention_count, last_backup_date
                              FROM backup_settings WHERE id = 1")->fetch_assoc();
    if (!$settings || !(bool)$settings['enabled']) {
        json_response(['success' => true, 'performed' => false, 'reason' => 'disabled']);
    } else {
        $clock = $conn->query("SELECT CURDATE() AS today, CURTIME() AS now_time")->fetch_assoc();
        $already_done = $settings['last_backup_date'] === $clock['today'];
        $time_due = $clock['now_time'] >= $settings['backup_time'];
        if ($already_done || !$time_due) {
            json_response(['success' => true, 'performed' => false, 'reason' => $already_done ? 'already-complete' : 'not-due']);
        } else {
            $backup = create_database_backup($conn, 'automatic');
            $update = $conn->prepare("UPDATE backup_settings
                                     SET last_backup_date = ?, last_backup_filename = ? WHERE id = 1");
            $update->bind_param('ss', $clock['today'], $backup['filename']);
            $update->execute();
            $update->close();
            $removed = prune_automatic_backups((int)$settings['retention_count']);
            audit_log($conn, null, 'Backup Management', 'Automatic Backup',
                'Created automatic backup ' . $backup['filename'] . '; expired automatic backups removed: ' . $removed,
                'backup', null, 'system');
            json_response(['success' => true, 'performed' => true, 'backup' => $backup, 'removed' => $removed]);
        }
    }
} catch (Throwable $error) {
    json_error('Automatic backup failed: ' . $error->getMessage(), 500);
} finally {
    try { $conn->query("SELECT RELEASE_LOCK('nuteck_production_auto_backup')"); } catch (Throwable $ignored) {}
}
$conn->close();
?>
