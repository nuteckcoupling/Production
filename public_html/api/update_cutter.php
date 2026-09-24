<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
$cutter_num = trim($data['cutter_num'] ?? '');
$cutter_type = trim($data['cutter_type'] ?? '');
$lead_angle = trim((string)($data['lead_angle'] ?? ''));
$rpm_stroke = trim($data['rpm_stroke'] ?? '');
$remarks = trim($data['remarks'] ?? '');
$status = $data['status'] ?? 'Active';

if (!$id || $cutter_num === '') {
    http_response_code(400);
    json_response(['error' => 'Valid cutter and Cutter Number are required']);
    exit;
}
if (!in_array($status, ['Active', 'Not Active'], true)) {
    http_response_code(400);
    json_response(['error' => 'Invalid cutter status']);
    exit;
}
if ($lead_angle !== '' && !is_numeric($lead_angle)) {
    http_response_code(400);
    json_response(['error' => 'Lead Angle must be a number']);
    exit;
}
$lead_angle_value = $lead_angle === '' ? null : $lead_angle;

$check = $conn->prepare("SELECT id FROM cutters WHERE cutter_num = ? AND id <> ? LIMIT 1");
$check->bind_param("si", $cutter_num, $id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    json_response(['error' => 'Cutter Number already exists']);
    exit;
}
$check->close();

$stmt = $conn->prepare("UPDATE cutters SET cutter_num = ?, cutter_type = ?, lead_angle = ?,
    rpm_stroke = ?, status = ?, remarks = ? WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("ssssssi", $cutter_num, $cutter_type, $lead_angle_value, $rpm_stroke, $status, $remarks, $id);

try {
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $exists = $conn->prepare("SELECT id FROM cutters WHERE id = ? AND deleted_at IS NULL");
        $exists->bind_param("i", $id);
        $exists->execute();
        if ($exists->get_result()->num_rows === 0) {
            http_response_code(404);
            json_response(['error' => 'Cutter not found']);
            exit;
        }
        $exists->close();
    }
    audit_log($conn, $user, 'Cutter Management', 'Updated',
        'Updated cutter ' . $cutter_num . ' (' . $status . ')', 'cutter', (int)$id);
    json_response(['success' => true, 'id' => (int)$id]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        http_response_code(409);
        json_response(['error' => 'Cutter Number already exists']);
    } else {
        http_response_code(500);
        json_response(['error' => 'Unable to update cutter']);
    }
}
$stmt->close();
$conn->close();
?>
