<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) json_error('Valid coupling is required', 400);

$stmt = $conn->prepare("UPDATE parts SET status = 'Active' WHERE id = ? AND status = 'Inactive'");
$stmt->bind_param('i', $id);
try {
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        json_error('Deleted coupling not found', 404);
    }
    audit_log($conn, $user, 'Coupling Management', 'Restored',
        'Restored coupling ID ' . $id, 'part', (int)$id);
    json_response(['success' => true, 'id' => (int)$id, 'message' => 'Coupling restored successfully.']);
} catch (Throwable $error) {
    json_error('Unable to restore coupling', 500);
}
$stmt->close();
$conn->close();
?>
