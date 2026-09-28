<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/migration_helpers.php';
$user = require_admin();
$data = json_input();
$password = (string)($data['password'] ?? '');
$confirmation = trim((string)($data['confirmation'] ?? ''));
if ($password === '' || $confirmation !== 'MIGRATE') {
    json_error('Admin password and MIGRATE confirmation are required', 400);
}

$password_stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ? AND status = 'Active' LIMIT 1");
$password_stmt->bind_param('i', $user['id']);
$password_stmt->execute();
$password_row = $password_stmt->get_result()->fetch_assoc();
$password_stmt->close();
if (!$password_row || !password_verify($password, $password_row['password_hash'])) {
    json_error('Admin password is incorrect', 401);
}

$lock = false;
try {
    $lock_row = $conn->query("SELECT GET_LOCK('nuteck_production_migrations', 0) AS acquired")->fetch_assoc();
    $lock = (int)($lock_row['acquired'] ?? 0) === 1;
    if (!$lock) json_error('Another migration is already running', 409);

    $status = migration_status($conn);
    $pending = array_values(array_filter($status, fn($row) => $row['status'] === 'Pending'));
    if (!$pending) json_response(['success' => true, 'applied' => [], 'backup' => null]);
    else {
        $backup = create_database_backup($conn, 'safety');
        $applied = apply_pending_migrations($conn, (int)$user['id']);
        audit_log($conn, $user, 'System Reliability', 'Database Migrated',
            'Applied migrations: ' . implode(', ', $applied) . '; safety backup: ' . $backup['filename'],
            'schema_migration', null);
        json_response(['success' => true, 'applied' => $applied, 'backup' => $backup['filename']]);
    }
} catch (RuntimeException $error) {
    if (str_starts_with($error->getMessage(), 'Migration history mismatch')) {
        application_log_error('Apply database migrations', $error);
        json_error($error->getMessage(), 409);
    }
    json_server_error('Apply database migrations', $error, 'Database migration failed');
} catch (Throwable $error) {
    json_server_error('Apply database migrations', $error, 'Database migration failed');
} finally {
    if ($lock) {
        try { $conn->query("SELECT RELEASE_LOCK('nuteck_production_migrations')"); } catch (Throwable $ignored) {}
    }
}
$conn->close();
?>
