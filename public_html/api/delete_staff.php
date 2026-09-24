<?php
require __DIR__ . "/bootstrap.php";
require_admin();
$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) json_error('Valid staff member is required', 400);

$stmt = $conn->prepare("SELECT id FROM operators WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows === 1;
$stmt->close();
if (!$exists) json_error('Staff member not found', 404);
if (staff_has_active_work($conn, $id)) {
    json_error('Staff has a running job or shift. End the work before deleting.', 409);
}
if (staff_linked_user($conn, $id)) {
    json_error('Staff is linked to a login account and cannot be deleted. Deactivate the staff member instead.', 409);
}
if (staff_usage_count($conn, $id) > 0) {
    json_error('Staff has production history. Deactivate instead of deleting.', 409);
}

$delete = $conn->prepare("DELETE FROM operators WHERE id = ?");
$delete->bind_param('i', $id);
$delete->execute();
if ($delete->affected_rows !== 1) json_error('Unable to delete staff member', 500);
json_response(['success' => true]);
$delete->close();
$conn->close();
?>
