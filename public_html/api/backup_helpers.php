<?php
function backup_directory(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
}

function ensure_backup_directory(): string
{
    $directory = backup_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the protected backup directory');
    }
    return $directory;
}

function valid_backup_filename(string $filename): bool
{
    return preg_match('/^production-(?:backup|safety)-\d{8}-\d{6}-[a-f0-9]{8}\.sql$/', $filename) === 1;
}

function backup_file_path(string $filename): string
{
    if (!valid_backup_filename($filename)) throw new RuntimeException('Invalid backup file', 400);
    return ensure_backup_directory() . DIRECTORY_SEPARATOR . $filename;
}

function backup_sql_value(mysqli $conn, $value): string
{
    if ($value === null) return 'NULL';
    return "'" . $conn->real_escape_string((string)$value) . "'";
}

function backup_write($handle, string $content): void
{
    if (fwrite($handle, $content) === false) throw new RuntimeException('Unable to write backup file');
}

function create_database_backup(mysqli $conn, string $kind = 'backup'): array
{
    if (!in_array($kind, ['backup', 'safety'], true)) $kind = 'backup';
    $directory = ensure_backup_directory();
    $filename = 'production-' . $kind . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.sql';
    $final_path = $directory . DIRECTORY_SEPARATOR . $filename;
    $temporary_path = $final_path . '.tmp';
    $handle = fopen($temporary_path, 'xb');
    if (!$handle) throw new RuntimeException('Unable to create backup file');

    try {
        $database = (string)$conn->query('SELECT DATABASE() AS db')->fetch_assoc()['db'];
        backup_write($handle, "-- NU-TECK Production System Backup\n");
        backup_write($handle, '-- Database: ' . $database . "\n");
        backup_write($handle, '-- Created: ' . date('Y-m-d H:i:s') . "\n\n");
        backup_write($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $conn->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $conn->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');
        $tables_result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = [];
        while ($table_row = $tables_result->fetch_array(MYSQLI_NUM)) $tables[] = $table_row[0];

        foreach ($tables as $table) {
            $quoted_table = '`' . str_replace('`', '``', $table) . '`';
            $create_row = $conn->query('SHOW CREATE TABLE ' . $quoted_table)->fetch_array(MYSQLI_NUM);
            backup_write($handle, '-- Table ' . $quoted_table . "\nDROP TABLE IF EXISTS " . $quoted_table . ";\n");
            backup_write($handle, $create_row[1] . ";\n\n");

            $columns = [];
            $column_result = $conn->query('SHOW COLUMNS FROM ' . $quoted_table);
            while ($column = $column_result->fetch_assoc()) {
                if (stripos((string)$column['Extra'], 'GENERATED') !== false) continue;
                $columns[] = $column['Field'];
            }
            if (!$columns) continue;
            $quoted_columns = array_map(fn($column) => '`' . str_replace('`', '``', $column) . '`', $columns);
            $data_result = $conn->query('SELECT ' . implode(', ', $quoted_columns) . ' FROM ' . $quoted_table);
            while ($row = $data_result->fetch_assoc()) {
                $values = [];
                foreach ($columns as $column) $values[] = backup_sql_value($conn, $row[$column]);
                backup_write($handle, 'INSERT INTO ' . $quoted_table . ' (' . implode(', ', $quoted_columns) .
                    ') VALUES (' . implode(', ', $values) . ");\n");
            }
            backup_write($handle, "\n");
        }
        $conn->commit();
        backup_write($handle, "SET FOREIGN_KEY_CHECKS=1;\n-- End of backup\n");
        fclose($handle);
        $handle = null;
        if (!rename($temporary_path, $final_path)) throw new RuntimeException('Unable to finalize backup file');
        @chmod($final_path, 0640);
        return [
            'filename' => $filename,
            'size' => filesize($final_path),
            'created_at' => date('Y-m-d H:i:s', filemtime($final_path)),
            'kind' => $kind
        ];
    } catch (Throwable $error) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        if (is_resource($handle)) fclose($handle);
        if (is_file($temporary_path)) unlink($temporary_path);
        throw $error;
    }
}

function list_database_backups(): array
{
    $directory = ensure_backup_directory();
    $files = [];
    foreach (scandir($directory) ?: [] as $filename) {
        if (!valid_backup_filename($filename)) continue;
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($path)) continue;
        $files[] = [
            'filename' => $filename,
            'size' => filesize($path),
            'created_at' => date('Y-m-d H:i:s', filemtime($path)),
            'kind' => str_starts_with($filename, 'production-safety-') ? 'safety' : 'backup'
        ];
    }
    usort($files, fn($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: strcmp($b['filename'], $a['filename']));
    return $files;
}

function restore_database_backup(mysqli $conn, string $filename): void
{
    $path = backup_file_path($filename);
    if (!is_file($path)) throw new RuntimeException('Backup file not found', 404);
    $size = filesize($path);
    if ($size === false || $size < 20 || $size > 100 * 1024 * 1024) {
        throw new RuntimeException('Backup file size is invalid', 400);
    }
    $sql = file_get_contents($path);
    if ($sql === false || !str_starts_with($sql, '-- NU-TECK Production System Backup')) {
        throw new RuntimeException('This is not a valid Production System backup', 400);
    }
    set_time_limit(0);
    if (!$conn->multi_query($sql)) throw new RuntimeException('Restore failed: ' . $conn->error);
    do {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) $result->free();
        if (!$conn->more_results()) break;
    } while ($conn->next_result());
    if ($conn->errno) throw new RuntimeException('Restore failed: ' . $conn->error);
}
?>
