<?php
require __DIR__ . '/bootstrap.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['manage']) && $_GET['manage'] === '1') {
        $result = $conn->query("SELECT v.id, v.name, v.address, v.phone, v.status, v.created_at,
            COUNT(ch.id) AS challan_count
            FROM cutter_service_vendors v
            LEFT JOIN cutter_service_challans ch ON ch.vendor_id = v.id
            GROUP BY v.id, v.name, v.address, v.phone, v.status, v.created_at
            ORDER BY v.status = 'Active' DESC, v.name, v.id");
        json_response($result->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    $result = $conn->query("SELECT id, name, address, phone FROM cutter_service_vendors WHERE status = 'Active' ORDER BY name, id");
    json_response($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$user = require_operator_supervisor();
$data = json_input();
$action = trim((string)($data['action'] ?? 'create'));
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if ($action === 'create' || $action === 'update') {
    $name = trim((string)($data['name'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    if ($name === '' || $address === '' || strlen($name) > 150 || strlen($address) > 500 || strlen($phone) > 30) {
        json_error('Enter a vendor name and address. Phone is optional.', 400);
    }
    $phone = $phone === '' ? null : $phone;

    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO cutter_service_vendors (name, address, phone) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name, $address, $phone);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        $stmt->close();
        audit_log($conn, $user, 'Cutter Service', 'Vendor Added', 'Added vendor ' . $name, 'cutter_service_vendor', $id);
        json_response(['success' => true, 'id' => $id, 'name' => $name, 'address' => $address, 'phone' => $phone, 'status' => 'Active'], 201);
        exit;
    }

    if (!$id) json_error('Select a valid vendor to update.', 400);
    $stmt = $conn->prepare("UPDATE cutter_service_vendors SET name = ?, address = ?, phone = ? WHERE id = ?");
    $stmt->bind_param('sssi', $name, $address, $phone, $id);
    $stmt->execute();
    $updated = $stmt->affected_rows;
    $stmt->close();
    $check = $conn->prepare('SELECT id FROM cutter_service_vendors WHERE id = ?');
    $check->bind_param('i', $id);
    $check->execute();
    $exists = (bool)$check->get_result()->fetch_assoc();
    $check->close();
    if (!$exists) json_error('Vendor not found.', 404);
    audit_log($conn, $user, 'Cutter Service', 'Vendor Updated', 'Updated vendor ' . $name, 'cutter_service_vendor', $id);
    json_response(['success' => true, 'id' => $id, 'name' => $name, 'address' => $address, 'phone' => $phone]);
    exit;
}

if (!$id) json_error('Select a valid vendor.', 400);

if ($action === 'status') {
    $status = (string)($data['status'] ?? '');
    if (!in_array($status, ['Active', 'Inactive'], true)) json_error('Select Active or Inactive status.', 400);
    $stmt = $conn->prepare('UPDATE cutter_service_vendors SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $exists = $stmt->affected_rows > 0;
    $stmt->close();
    if (!$exists) {
        $check = $conn->prepare('SELECT id FROM cutter_service_vendors WHERE id = ?');
        $check->bind_param('i', $id);
        $check->execute();
        $exists = (bool)$check->get_result()->fetch_assoc();
        $check->close();
    }
    if (!$exists) json_error('Vendor not found.', 404);
    audit_log($conn, $user, 'Cutter Service', 'Vendor Status Changed', 'Set vendor #' . $id . ' to ' . $status, 'cutter_service_vendor', $id);
    json_response(['success' => true, 'id' => $id, 'status' => $status]);
    exit;
}

if ($action === 'delete') {
    $stmt = $conn->prepare('SELECT name, (SELECT COUNT(*) FROM cutter_service_challans WHERE vendor_id = cutter_service_vendors.id) AS challan_count FROM cutter_service_vendors WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $vendor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$vendor) json_error('Vendor not found.', 404);
    if ((int)$vendor['challan_count'] > 0) {
        json_error('This vendor is linked to challan history. Deactivate it instead of deleting.', 409);
    }
    $stmt = $conn->prepare('DELETE FROM cutter_service_vendors WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    audit_log($conn, $user, 'Cutter Service', 'Vendor Deleted', 'Deleted unused vendor ' . $vendor['name'], 'cutter_service_vendor', $id);
    json_response(['success' => true, 'id' => $id]);
    exit;
}

json_error('Unknown vendor action.', 400);
