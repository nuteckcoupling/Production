<?php
require __DIR__ . "/bootstrap.php";
require_admin();
$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    json_error('Valid shift is required', 400);
}
$stmt = $conn->prepare("SELECT code FROM shifts WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$shift = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$shift) {
    json_error('Shift not found', 404);
}
if (shift_usage_count($conn, $shift['code']) > 0) {
    json_error('This shift has production or ISO history. Deactivate it instead of deleting.', 409);
}
$delete = $conn->prepare("DELETE FROM shifts WHERE id = ?");
$delete->bind_param('i', $id);
$delete->execute();
if ($delete->affected_rows !== 1) {
    json_error('Unable to delete shift', 500);
}
json_response(['success' => true]);
$delete->close();
$conn->close();
?>
