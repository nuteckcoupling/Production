<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) json_error('Valid coupling is required', 400);

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT part_name FROM parts WHERE id = ? AND status = 'Active' FOR UPDATE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $part = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$part) throw new RuntimeException('Coupling not found', 404);

    $active = $conn->prepare("SELECT id FROM production_jobs WHERE part_id = ? AND status IN ('Running','Handover Pending','Breakdown','Stopped') LIMIT 1");
    $active->bind_param('i', $id);
    $active->execute();
    $has_active_job = $active->get_result()->num_rows > 0;
    $active->close();
    if ($has_active_job) throw new RuntimeException('Coupling is used by an active or stopped job and cannot be deleted', 409);

    $update = $conn->prepare("UPDATE parts SET status = 'Inactive' WHERE id = ?");
    $update->bind_param('i', $id);
    $update->execute();
    $update->close();
    $conn->commit();

    audit_log($conn, $user, 'Coupling Management', 'Deleted',
        'Removed coupling ' . $part['part_name'] . ' from active dropdowns', 'part', (int)$id);
    json_response(['success' => true, 'id' => (int)$id, 'message' => 'Coupling deleted. Production history remains safe.']);
} catch (RuntimeException $error) {
    $conn->rollback();
    json_error($error->getMessage(), $error->getCode() >= 400 ? $error->getCode() : 400);
} catch (Throwable $error) {
    $conn->rollback();
    json_error('Unable to delete coupling', 500);
}
$conn->close();
?>
