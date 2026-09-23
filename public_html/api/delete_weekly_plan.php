<?php
require __DIR__ . "/bootstrap.php";
require_auth();
$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    json_error('Valid Weekly Plan is required', 400);
}
$stmt = $conn->prepare('DELETE FROM weekly_plans WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->affected_rows === 0) {
    json_error('Weekly Plan not found', 404);
}
json_response(['success' => true]);
$stmt->close();
$conn->close();
?>
