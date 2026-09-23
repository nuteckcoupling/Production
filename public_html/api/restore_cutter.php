<?php
require __DIR__ . "/bootstrap.php";
require_operator_supervisor();

$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    json_response(['error' => 'Valid cutter is required']);
    exit;
}

$stmt = $conn->prepare("UPDATE cutters SET deleted_at = NULL, status = 'Active'
    WHERE id = ? AND deleted_at IS NOT NULL");
$stmt->bind_param("i", $id);
try {
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        http_response_code(404);
        json_response(['error' => 'Deleted cutter not found']);
    } else {
        json_response(['success' => true, 'id' => (int)$id, 'message' => 'Cutter restored as Active.']);
    }
} catch (Throwable $error) {
    http_response_code(500);
    json_response(['error' => 'Unable to restore cutter']);
}
$stmt->close();
$conn->close();
?>
