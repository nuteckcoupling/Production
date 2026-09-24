<?php
require __DIR__ . "/bootstrap.php";
require_admin();
$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    json_error('Valid machine is required', 400);
}
$stmt = $conn->prepare("SELECT id FROM machines WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows === 1;
$stmt->close();
if (!$exists) {
    json_error('Machine not found', 404);
}
if (machine_has_active_job($conn, $id)) {
    json_error('Running machine cannot be deleted. End the current job first.', 409);
}
if (machine_usage_count($conn, $id) > 0) {
    json_error('This machine has production or ISO history. Deactivate it instead of deleting.', 409);
}
$delete = $conn->prepare("DELETE FROM machines WHERE id = ?");
$delete->bind_param('i', $id);
$delete->execute();
if ($delete->affected_rows !== 1) {
    json_error('Unable to delete machine', 500);
}
json_response(['success' => true]);
$delete->close();
$conn->close();
?>
