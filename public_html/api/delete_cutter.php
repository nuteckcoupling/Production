<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    json_response(['error' => 'Valid cutter is required']);
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT id, cutter_num FROM cutters WHERE id = ? AND deleted_at IS NULL FOR UPDATE");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $cutter = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$cutter) {
        $conn->rollback();
        http_response_code(404);
        json_response(['error' => 'Cutter not found']);
        exit;
    }

    $update = $conn->prepare("UPDATE cutters SET status = 'Not Active', deleted_at = NOW()
        WHERE id = ? AND deleted_at IS NULL");
    $update->bind_param("i", $id);
    $update->execute();
    $update->close();
    $action = 'trashed';
    $message = 'Cutter moved to Trash. Production history remains safe.';

    $conn->commit();
    audit_log($conn, $user, 'Cutter Management', 'Moved to Trash',
        'Moved cutter ' . $cutter['cutter_num'] . ' to Trash', 'cutter', (int)$id);
    json_response(['success' => true, 'id' => (int)$id, 'action' => $action, 'message' => $message]);
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    json_response(['error' => 'Unable to delete cutter']);
}
$conn->close();
?>
