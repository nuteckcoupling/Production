<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/attachment_helpers.php';
$user = require_auth();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$view = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_BOOLEAN);
if (!$id) json_error('Valid attachment is required', 400);
$stmt = $conn->prepare('SELECT entity_type, original_name, relative_path, mime_type, file_size FROM attachments WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$attachment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$attachment) json_error('Attachment not found', 404);
if (!attachment_can_read($user, $attachment['entity_type'])) json_error('Attachment access denied', 403);
try {
    $path = attachment_absolute_path($attachment['relative_path']);
} catch (Throwable $error) {
    json_server_error('Download attachment path', $error, 'Attachment is unavailable');
}
if (!is_file($path)) json_error('Attachment file is missing', 404);
$download_name = preg_replace('/[^a-zA-Z0-9._ -]/', '_', basename($attachment['original_name']));
header_remove('Content-Type');
header('Content-Type: ' . $attachment['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($view ? 'inline' : 'attachment') . '; filename="' . addcslashes($download_name, '"\\') . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
$conn->close();
exit;
?>
