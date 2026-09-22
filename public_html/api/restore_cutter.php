<?php
require "config.php";
require "auth.php";
require_operator_supervisor();

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid cutter is required']);
    exit;
}

$stmt = $conn->prepare("UPDATE cutters SET deleted_at = NULL, status = 'Active'
    WHERE id = ? AND deleted_at IS NOT NULL");
$stmt->bind_param("i", $id);
try {
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        http_response_code(404);
        echo json_encode(['error' => 'Deleted cutter not found']);
    } else {
        echo json_encode(['success' => true, 'id' => (int)$id, 'message' => 'Cutter restored as Active.']);
    }
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to restore cutter']);
}
$stmt->close();
$conn->close();
?>
