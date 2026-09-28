<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/attachment_helpers.php';
$user = require_auth();
$entity_type = trim((string)($_POST['entity_type'] ?? ''));
$entity_id = filter_var($_POST['entity_id'] ?? null, FILTER_VALIDATE_INT);
$category = trim((string)($_POST['category'] ?? ''));
if (!$entity_id || !attachment_can_upload($user, $entity_type, $category)) {
    json_error('You do not have permission to upload this attachment type', 403);
}
if (!attachment_entity_exists($conn, $entity_type, (int)$entity_id)) json_error('Related record not found', 404);

$stored = [];
try {
    $stored = store_secure_attachment($_FILES['attachment'] ?? []);
    $user_id = (int)$user['id'];
    $stmt = $conn->prepare('INSERT INTO attachments
        (entity_type, entity_id, category, original_name, stored_name, relative_path, mime_type, file_size, uploaded_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sisssssii', $entity_type, $entity_id, $category, $stored['original_name'],
        $stored['stored_name'], $stored['relative_path'], $stored['mime_type'], $stored['file_size'], $user_id);
    $stmt->execute();
    $attachment_id = (int)$stmt->insert_id;
    $stmt->close();
    audit_log($conn, $user, 'Attachments', 'Attachment Uploaded',
        $category . ': ' . $stored['original_name'], $entity_type, (int)$entity_id);
    json_response(['success' => true, 'attachment_id' => $attachment_id], 201);
} catch (RuntimeException $error) {
    if (!empty($stored['path']) && is_file($stored['path'])) unlink($stored['path']);
    $code = $error->getCode();
    if ($code === 400) json_error($error->getMessage(), 400);
    json_server_error('Upload attachment', $error, 'Unable to upload attachment');
} catch (Throwable $error) {
    if (!empty($stored['path']) && is_file($stored['path'])) unlink($stored['path']);
    json_server_error('Upload attachment', $error, 'Unable to upload attachment');
}
$conn->close();
?>
