<?php
function migrations_directory(): string
{
    $configured = getenv('PRODUCTION_MIGRATIONS_PATH');
    $project_root = dirname(__DIR__, 2);
    $candidates = array_filter([
        $configured === false ? null : $configured,
        $project_root . DIRECTORY_SEPARATOR . 'migrations',
        $project_root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'migrations'
    ]);
    foreach ($candidates as $directory) {
        if (is_dir($directory)) return $directory;
    }
    throw new RuntimeException('Migration directory is unavailable');
}

function ensure_schema_migrations_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version VARCHAR(190) NOT NULL UNIQUE,
        checksum CHAR(64) NOT NULL,
        applied_by_user_id INT DEFAULT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_schema_migrations_applied (applied_at)
    )");
}

function migration_checksum(string $path): string
{
    $contents = file_get_contents($path);
    if ($contents === false) throw new RuntimeException('Unable to read migration file ' . basename($path));
    return hash('sha256', str_replace(["\r\n", "\r"], "\n", $contents));
}

function migration_status(mysqli $conn): array
{
    ensure_schema_migrations_table($conn);
    $applied = [];
    $result = $conn->query('SELECT version, checksum, applied_by_user_id, applied_at FROM schema_migrations ORDER BY version');
    while ($row = $result->fetch_assoc()) $applied[$row['version']] = $row;

    $rows = [];
    foreach (glob(migrations_directory() . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $path) {
        $version = basename($path);
        $checksum = migration_checksum($path);
        $record = $applied[$version] ?? null;
        $status = $record === null ? 'Pending' : (hash_equals($record['checksum'], $checksum) ? 'Applied' : 'Changed');
        $rows[] = [
            'version' => $version,
            'checksum' => $checksum,
            'status' => $status,
            'applied_at' => $record['applied_at'] ?? null,
            'applied_by_user_id' => isset($record['applied_by_user_id']) ? (int)$record['applied_by_user_id'] : null
        ];
        unset($applied[$version]);
    }
    foreach ($applied as $version => $record) {
        $rows[] = [
            'version' => $version,
            'checksum' => $record['checksum'],
            'status' => 'Missing File',
            'applied_at' => $record['applied_at'],
            'applied_by_user_id' => $record['applied_by_user_id'] === null ? null : (int)$record['applied_by_user_id']
        ];
    }
    usort($rows, fn($a, $b) => strcmp($a['version'], $b['version']));
    return $rows;
}

function execute_migration_sql(mysqli $conn, string $sql): void
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    if (trim($sql) === '') return;
    if (!$conn->multi_query($sql)) throw new RuntimeException('Migration SQL failed: ' . $conn->error);
    do {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) $result->free();
        if (!$conn->more_results()) break;
        if (!$conn->next_result()) throw new RuntimeException('Migration SQL failed: ' . $conn->error);
    } while (true);
}

function apply_pending_migrations(mysqli $conn, int $user_id): array
{
    $rows = migration_status($conn);
    $unsafe = array_values(array_filter($rows, fn($row) => in_array($row['status'], ['Changed', 'Missing File'], true)));
    if ($unsafe) throw new RuntimeException('Migration history mismatch detected. Application stopped for safety.');
    $pending = array_values(array_filter($rows, fn($row) => $row['status'] === 'Pending'));
    $applied = [];
    foreach ($pending as $migration) {
        $path = migrations_directory() . DIRECTORY_SEPARATOR . $migration['version'];
        $sql = file_get_contents($path);
        if ($sql === false) throw new RuntimeException('Unable to read migration ' . $migration['version']);
        execute_migration_sql($conn, $sql);
        $stmt = $conn->prepare('INSERT INTO schema_migrations (version, checksum, applied_by_user_id) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $migration['version'], $migration['checksum'], $user_id);
        $stmt->execute();
        $stmt->close();
        $applied[] = $migration['version'];
    }
    return $applied;
}
?>
