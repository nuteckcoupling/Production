<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/attachment_helpers.php';
$user = require_auth();
$entity_type = trim((string)($_POST['entity_type'] ?? ''));
$entity_id = filter_var($_POST['entity_id'] ?? null, FILTER_VALIDATE_INT);
$category = trim((string)($_POST['category'] ?? ''));
$component = trim((string)($_POST['component'] ?? ''));
$drawing_no = trim((string)($_POST['drawing_no'] ?? ''));
if (!$entity_id || !attachment_can_upload($user, $entity_type, $category)) {
    json_error('You do not have permission to upload this attachment type', 403);
}
if (!attachment_entity_exists($conn, $entity_type, (int)$entity_id)) json_error('Related record not found', 404);

if ($entity_type === 'part' && $category === 'Coupling Drawing') {
    if ($component === '' || $drawing_no === '') json_error('Component and Drawing Number are required', 400);
    if (strlen($component) > 50 || strlen($drawing_no) > 100) json_error('Component or Drawing Number is too long', 400);
    $component_stmt = $conn->prepare('SELECT id FROM part_components WHERE part_id = ? AND component_name = ? LIMIT 1');
    $component_stmt->bind_param('is', $entity_id, $component);
    $component_stmt->execute();
    $valid_component = (bool)$component_stmt->get_result()->fetch_assoc();
    $component_stmt->close();
    if (!$valid_component) json_error('Selected component is not available for this coupling', 400);

    $duplicate_stmt = $conn->prepare("SELECT id FROM attachments
        WHERE entity_type = 'part' AND entity_id = ? AND category = 'Coupling Drawing'
          AND component = ? AND drawing_no = ? LIMIT 1");
    $duplicate_stmt->bind_param('iss', $entity_id, $component, $drawing_no);
    $duplicate_stmt->execute();
    $duplicate = (bool)$duplicate_stmt->get_result()->fetch_assoc();
    $duplicate_stmt->close();
    if ($duplicate) json_error('This Drawing Number already exists for the selected component', 409);
} else {
    $component = '';
    $drawing_no = '';
}

$stored = [];
try {
    $stored = store_secure_attachment($_FILES['attachment'] ?? []);
    $user_id = (int)$user['id'];
    $stmt = $conn->prepare("INSERT INTO attachments
        (entity_type, entity_id, category, component, drawing_no, original_name, stored_name, relative_path, mime_type, file_size, uploaded_by_user_id)
        VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sisssssssii', $entity_type, $entity_id, $category, $component, $drawing_no, $stored['original_name'],
        $stored['stored_name'], $stored['relative_path'], $stored['mime_type'], $stored['file_size'], $user_id);
    $stmt->execute();
    $attachment_id = (int)$stmt->insert_id;
    $stmt->close();
    audit_log($conn, $user, 'Attachments', 'Attachment Uploaded',
        $category . ($drawing_no === '' ? '' : ' ' . $drawing_no) . ': ' . $stored['original_name'],
        $entity_type, (int)$entity_id);
    json_response(['success' => true, 'attachment_id' => $attachment_id], 201);
} catch (RuntimeException $error) {
    if (!empty($stored['path']) && is_file($stored['path'])) unlink($stored['path']);
    $code = $error->getCode();
    if ($code === 400) json_error($error->getMessage(), 400);
    if ($code === 1062) json_error('This Drawing Number already exists for the selected component', 409);
    json_server_error('Upload attachment', $error, 'Unable to upload attachment');
} catch (Throwable $error) {
    if (!empty($stored['path']) && is_file($stored['path'])) unlink($stored['path']);
    if ((int)$error->getCode() === 1062) json_error('This Drawing Number already exists for the selected component', 409);
    json_server_error('Upload attachment', $error, 'Unable to upload attachment');
}
$conn->close();
?>
