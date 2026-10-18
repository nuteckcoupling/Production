<?php
require __DIR__ . '/bootstrap.php';
$user = require_operator_supervisor();
$data = json_input();
$item_id = filter_var($data['item_id'] ?? null, FILTER_VALIDATE_INT);
$service_done = filter_var($data['service_done'] ?? null, FILTER_VALIDATE_INT);
$service_date = trim((string)($data['service_date'] ?? ''));
$received_date = trim((string)($data['received_date'] ?? ''));
$condition = trim((string)($data['return_condition'] ?? ''));
$remarks = trim((string)($data['return_remarks'] ?? ''));
$serviceDate = DateTime::createFromFormat('!Y-m-d', $service_date);
$receivedDate = DateTime::createFromFormat('!Y-m-d', $received_date);
if (!$item_id || !in_array($service_done, [0, 1], true) || !$serviceDate || $serviceDate->format('Y-m-d') !== $service_date
    || !$receivedDate || $receivedDate->format('Y-m-d') !== $received_date || $service_date > $received_date
    || strlen($condition) > 80 || strlen($remarks) > 255) {
    json_error('Enter valid service/receive dates and return details.', 400);
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT i.*, c.cutter_num, ch.challan_no FROM cutter_service_challan_items i
        JOIN cutters c ON c.id = i.cutter_id JOIN cutter_service_challans ch ON ch.id = i.challan_id
        WHERE i.id = ? FOR UPDATE");
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$item) throw new RuntimeException('Challan item not found.', 404);
    if ($item['received_at'] !== null) throw new RuntimeException('This cutter has already been received.', 409);
    $service_event_id = null;
    if ($service_done === 1) {
        $stmt = $conn->prepare('INSERT INTO cutter_service_events (cutter_id, event_type, event_date, remarks, created_by_user_id) VALUES (?, ?, ?, ?, ?)');
        $user_id = (int)$user['id'];
        $service_remarks = implode(' · ', array_filter([
            trim((string)$item['sent_remarks']),
            $condition === '' ? '' : 'Return condition: ' . $condition,
            $remarks
        ]));
        $service_remarks = $service_remarks === '' ? null : substr($service_remarks, 0, 255);
        $stmt->bind_param('isssi', $item['cutter_id'], $item['service_type'], $service_date, $service_remarks, $user_id);
        $stmt->execute();
        $service_event_id = (int)$stmt->insert_id;
        $stmt->close();
    }
    $received_at = $received_date . ' ' . date('H:i:s');
    $condition_value = $condition === '' ? null : $condition;
    $remarks_value = $remarks === '' ? null : $remarks;
    $stmt = $conn->prepare('UPDATE cutter_service_challan_items SET received_at = ?, service_done = ?, service_date = ?, return_condition = ?, return_remarks = ?, service_event_id = ?, received_by_user_id = ? WHERE id = ?');
    $stmt->bind_param('sisssiii', $received_at, $service_done, $service_date, $condition_value, $remarks_value, $service_event_id, $user['id'], $item_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE cutter_service_challans ch SET ch.status = CASE
        WHEN EXISTS (SELECT 1 FROM cutter_service_challan_items i WHERE i.challan_id = ch.id AND i.received_at IS NULL) THEN 'Partially Returned'
        ELSE 'Returned' END WHERE ch.id = ?");
    $stmt->bind_param('i', $item['challan_id']);
    $stmt->execute();
    $stmt->close();
    $conn->commit();
    audit_log($conn, $user, 'Cutter Service', 'Cutter Received', 'Received ' . $item['cutter_num'] . ' from ' . $item['challan_no'] . ($service_done ? ' and logged service in Tool History' : ' without service'), 'cutter_service_challan', (int)$item['challan_id']);
    json_response(['success' => true, 'service_event_id' => $service_event_id]);
} catch (RuntimeException $error) {
    $conn->rollback();
    json_error($error->getMessage(), in_array($error->getCode(), [400, 404, 409], true) ? $error->getCode() : 400);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    json_server_error('Cutter service item receive', $error, 'Unable to record cutter return.');
}
