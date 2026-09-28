<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/attachment_helpers.php';
$user = require_auth();
$entity_type = trim((string)($_GET['entity_type'] ?? ''));
$entity_id = filter_input(INPUT_GET, 'entity_id', FILTER_VALIDATE_INT);
if (!$entity_id || !attachment_can_read($user, $entity_type)) json_error('Attachment access denied', 403);
if (!attachment_entity_exists($conn, $entity_type, (int)$entity_id)) json_error('Related record not found', 404);
$stmt = $conn->prepare("SELECT a.id, a.category, a.original_name, a.mime_type, a.file_size, a.created_at,
        COALESCE(o.name, u.username) AS uploaded_by
    FROM attachments a
    JOIN users u ON u.id = a.uploaded_by_user_id
    LEFT JOIN operators o ON o.id = u.operator_id
    WHERE a.entity_type = ? AND a.entity_id = ?
    ORDER BY a.created_at DESC, a.id DESC");
$stmt->bind_param('si', $entity_type, $entity_id);
$stmt->execute();
$rows = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['file_size'] = (int)$row['file_size'];
    $rows[] = $row;
}
$stmt->close();
json_response($rows);
$conn->close();
?>
