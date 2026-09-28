<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/maintenance_helpers.php';
require_auth();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) json_error('Valid attachment is required', 400);
$stmt = $conn->prepare('SELECT original_name, stored_name, mime_type, file_size FROM maintenance_attachments WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$file) json_error('Attachment not found', 404);
$path = maintenance_upload_directory() . DIRECTORY_SEPARATOR . basename($file['stored_name']);
if (!is_file($path)) json_error('Attachment file is unavailable', 404);
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', $file['original_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
$conn->close();
?>
