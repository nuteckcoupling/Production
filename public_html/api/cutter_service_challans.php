<?php
require __DIR__ . '/bootstrap.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $vendors = $conn->query("SELECT id, name, address, phone FROM cutter_service_vendors WHERE status = 'Active' ORDER BY name, id")->fetch_all(MYSQLI_ASSOC);
    $challans = $conn->query("SELECT ch.id, ch.challan_no, ch.vendor_id, v.name AS vendor_name, v.address AS vendor_address,
        v.phone AS vendor_phone, ch.dispatch_date, ch.expected_return_date, ch.status, ch.remarks, ch.created_at,
        COUNT(i.id) AS cutter_count, SUM(i.received_at IS NULL) AS outstanding_count
        FROM cutter_service_challans ch JOIN cutter_service_vendors v ON v.id = ch.vendor_id
        JOIN cutter_service_challan_items i ON i.challan_id = ch.id
        GROUP BY ch.id, ch.challan_no, ch.vendor_id, v.name, v.address, v.phone, ch.dispatch_date,
            ch.expected_return_date, ch.status, ch.remarks, ch.created_at
        ORDER BY ch.id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
    $items = [];
    if ($challans) {
        $ids = array_map(static fn($row) => (int)$row['id'], $challans);
        $idList = implode(',', $ids);
        $result = $conn->query("SELECT i.id, i.challan_id, i.cutter_id, c.cutter_num, c.cutter_module, c.cutter_type,
            i.service_type, i.sent_remarks, i.received_at, i.service_done, i.service_date,
            i.return_condition, i.return_remarks, i.service_event_id
            FROM cutter_service_challan_items i JOIN cutters c ON c.id = i.cutter_id
            WHERE i.challan_id IN ($idList) ORDER BY i.challan_id DESC, i.id");
        $items = $result->fetch_all(MYSQLI_ASSOC);
    }
    $out = $conn->query("SELECT id FROM cutters WHERE status = 'Active' AND scrap_date IS NULL AND deleted_at IS NULL
        AND NOT EXISTS (SELECT 1 FROM cutter_service_challan_items i WHERE i.cutter_id = cutters.id AND i.received_at IS NULL)
        ORDER BY cutter_module, cutter_num")->fetch_all(MYSQLI_ASSOC);
    json_response(['vendors' => $vendors, 'challans' => $challans, 'items' => $items,
        'available_cutter_ids' => array_map(static fn($row) => (int)$row['id'], $out)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);
$user = require_operator_supervisor();
$data = json_input();
$vendor_id = filter_var($data['vendor_id'] ?? null, FILTER_VALIDATE_INT);
$dispatch_date = trim((string)($data['dispatch_date'] ?? ''));
$expected_date = trim((string)($data['expected_return_date'] ?? ''));
$remarks = trim((string)($data['remarks'] ?? ''));
$items = $data['items'] ?? null;
$date = DateTime::createFromFormat('!Y-m-d', $dispatch_date);
$expected = $expected_date === '' ? null : DateTime::createFromFormat('!Y-m-d', $expected_date);
if (!$vendor_id || !$date || $date->format('Y-m-d') !== $dispatch_date
    || ($expected_date !== '' && (!$expected || $expected->format('Y-m-d') !== $expected_date || $expected_date < $dispatch_date))
    || !is_array($items) || !$items || count($items) > 100 || strlen($remarks) > 255) {
    json_error('Enter a valid vendor, dispatch date, optional return date, and at least one cutter.', 400);
}

$normalized = [];
foreach ($items as $item) {
    $cutter_id = filter_var($item['cutter_id'] ?? null, FILTER_VALIDATE_INT);
    $service_type = $item['service_type'] ?? '';
    $sent_remarks = trim((string)($item['sent_remarks'] ?? ''));
    if (!$cutter_id || !in_array($service_type, ['Coating', 'Grinding', 'Both'], true) || strlen($sent_remarks) > 255) {
        json_error('Each selected cutter needs a valid service type.', 400);
    }
    if (isset($normalized[$cutter_id])) json_error('A cutter can only appear once in a challan.', 400);
    $normalized[$cutter_id] = ['service_type' => $service_type, 'sent_remarks' => $sent_remarks ?: null];
}

try {
    $conn->begin_transaction();
    $vendorStmt = $conn->prepare("SELECT id FROM cutter_service_vendors WHERE id = ? AND status = 'Active' FOR UPDATE");
    $vendorStmt->bind_param('i', $vendor_id);
    $vendorStmt->execute();
    $validVendor = $vendorStmt->get_result()->fetch_assoc();
    $vendorStmt->close();
    if (!$validVendor) throw new RuntimeException('Select an active service vendor.', 400);

    $stmt = $conn->prepare("INSERT INTO cutter_service_challans (vendor_id, dispatch_date, expected_return_date, status, remarks, created_by_user_id)
        VALUES (?, ?, ?, 'Issued', ?, ?)");
    $created_by = (int)$user['id'];
    $expected_value = $expected_date === '' ? null : $expected_date;
    $remarks_value = $remarks === '' ? null : $remarks;
    $stmt->bind_param('isssi', $vendor_id, $dispatch_date, $expected_value, $remarks_value, $created_by);
    $stmt->execute();
    $challan_id = (int)$stmt->insert_id;
    $stmt->close();
    $challan_no = 'CS-' . date('Y') . '-' . str_pad((string)$challan_id, 6, '0', STR_PAD_LEFT);
    $stmt = $conn->prepare('UPDATE cutter_service_challans SET challan_no = ? WHERE id = ?');
    $stmt->bind_param('si', $challan_no, $challan_id);
    $stmt->execute();
    $stmt->close();

    $cutterStmt = $conn->prepare("SELECT cutter_num FROM cutters WHERE id = ? AND status = 'Active' AND scrap_date IS NULL AND deleted_at IS NULL FOR UPDATE");
    $openStmt = $conn->prepare('SELECT id FROM cutter_service_challan_items WHERE cutter_id = ? AND received_at IS NULL LIMIT 1 FOR UPDATE');
    $jobStmt = $conn->prepare("SELECT id FROM production_jobs WHERE cutter_id = ? AND status IN ('Running','Handover Pending','Breakdown','Stopped') LIMIT 1 FOR UPDATE");
    $itemStmt = $conn->prepare('INSERT INTO cutter_service_challan_items (challan_id, cutter_id, service_type, sent_remarks) VALUES (?, ?, ?, ?)');
    foreach ($normalized as $cutter_id => $item) {
        $cutterStmt->bind_param('i', $cutter_id);
        $cutterStmt->execute();
        $cutter = $cutterStmt->get_result()->fetch_assoc();
        if (!$cutter) throw new RuntimeException('One selected cutter is inactive, scrapped, deleted, or missing.', 409);
        $openStmt->bind_param('i', $cutter_id);
        $openStmt->execute();
        if ($openStmt->get_result()->fetch_assoc()) throw new RuntimeException('Cutter ' . $cutter['cutter_num'] . ' is already out for service.', 409);
        $jobStmt->bind_param('i', $cutter_id);
        $jobStmt->execute();
        if ($jobStmt->get_result()->fetch_assoc()) throw new RuntimeException('Cutter ' . $cutter['cutter_num'] . ' is assigned to an open production job. End or change that job before dispatch.', 409);
        $itemStmt->bind_param('iiss', $challan_id, $cutter_id, $item['service_type'], $item['sent_remarks']);
        $itemStmt->execute();
    }
    $cutterStmt->close(); $openStmt->close(); $jobStmt->close(); $itemStmt->close();
    $conn->commit();
    audit_log($conn, $user, 'Cutter Service', 'Challan Issued', 'Issued ' . $challan_no . ' with ' . count($normalized) . ' cutters', 'cutter_service_challan', $challan_id);
    json_response(['success' => true, 'challan_id' => $challan_id, 'challan_no' => $challan_no], 201);
} catch (RuntimeException $error) {
    $conn->rollback();
    json_error($error->getMessage(), in_array($error->getCode(), [400, 409], true) ? $error->getCode() : 400);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    json_server_error('Cutter service challan create', $error, 'Unable to create cutter challan. Apply its database migration and retry.');
}
