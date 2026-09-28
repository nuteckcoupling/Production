<?php
require __DIR__ . '/bootstrap.php';
require_admin();
$limit = max(10, min(200, (int)($_GET['limit'] ?? 100)));
try {
    $path = application_log_directory() . DIRECTORY_SEPARATOR . 'application.jsonl';
    if (!is_file($path)) {
        json_response(['errors' => [], 'count' => 0]);
    } else {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_slice($lines, -$limit);
        $errors = [];
        foreach (array_reverse($lines) as $line) {
            $entry = json_decode($line, true);
            if (is_array($entry)) $errors[] = $entry;
        }
        json_response(['errors' => $errors, 'count' => count($errors)]);
    }
} catch (Throwable $error) {
    json_server_error('Read application error log', $error, 'Unable to read error log');
}
$conn->close();
?>
