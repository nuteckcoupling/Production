<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/migration_helpers.php';
require_admin();
try {
    $migrations = migration_status($conn);
    $counts = ['pending' => 0, 'applied' => 0, 'attention' => 0];
    foreach ($migrations as $migration) {
        if ($migration['status'] === 'Pending') $counts['pending']++;
        elseif ($migration['status'] === 'Applied') $counts['applied']++;
        else $counts['attention']++;
    }
    json_response(['migrations' => $migrations, 'counts' => $counts]);
} catch (Throwable $error) {
    json_server_error('Read migration status', $error, 'Unable to read migration status');
}
$conn->close();
?>
