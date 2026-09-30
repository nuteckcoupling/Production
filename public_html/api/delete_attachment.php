<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/attachment_helpers.php';
$user = require_operator_supervisor();
$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) json_error('Valid drawing is required', 400);

$stmt = $conn->prepare("SELECT id, entity_type, category, original_name, relative_path
    FROM attachments WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$attachment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$attachment) json_error('Drawing not found', 404);
if ($attachment['entity_type'] !== 'part' || $attachment['category'] !== 'Coupling Drawing') {
    json_error('Only coupling drawings can be deleted here', 400);
}

try {
    $path = attachment_absolute_path($attachment['relative_path']);
    $delete = $conn->prepare('DELETE FROM attachments WHERE id = ?');
    $delete->bind_param('i', $id);
    $delete->execute();
    $delete->close();
    if (is_file($path) && !unlink($path)) error_log('Unable to remove attachment file: ' . $path);
    audit_log($conn, $user, 'Couplings', 'Delete Drawing',
        'Deleted coupling drawing ' . $attachment['original_name'], 'attachment', (int)$id);
    json_response(['success' => true, 'message' => 'Drawing deleted successfully.']);
} catch (Throwable $error) {
    json_server_error('Delete attachment', $error, 'Unable to delete drawing');
}
$conn->close();
?>
