<?php
require __DIR__ . "/bootstrap.php";
require_admin();
$result = $conn->query("SELECT enabled, TIME_FORMAT(backup_time, '%H:%i') AS backup_time,
                               retention_count, last_backup_date, last_backup_filename, updated_at
                        FROM backup_settings WHERE id = 1");
$settings = $result->fetch_assoc();
if (!$settings) json_error('Backup settings not found', 404);
$settings['enabled'] = (bool)$settings['enabled'];
$settings['retention_count'] = (int)$settings['retention_count'];
json_response($settings);
$conn->close();
?>
