<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$cutter_num = trim($data['cutter_num'] ?? '');
$module = trim($data['module'] ?? '');
$cutter_type = trim($data['cutter_type'] ?? '');
$lead_angle = trim((string)($data['lead_angle'] ?? ''));
$rpm_stroke = trim($data['rpm_stroke'] ?? '');
$remarks = trim($data['remarks'] ?? '');
$status = $data['status'] ?? 'Active';

if ($cutter_num === '') {
    http_response_code(400);
    json_response(["error" => "Cutter Number is required"]);
    exit;
}

if (!in_array($status, ['Active', 'Not Active'], true)) {
    http_response_code(400);
    json_response(["error" => "Invalid cutter status"]);
    exit;
}

if ($lead_angle !== '' && !is_numeric($lead_angle)) {
    http_response_code(400);
    json_response(["error" => "Lead Angle must be a number"]);
    exit;
}
$lead_angle_value = $lead_angle === '' ? null : $lead_angle;

$check = $conn->prepare("SELECT id FROM cutters WHERE cutter_num = ? AND COALESCE(cutter_module, '') = ? LIMIT 1");
$check->bind_param("ss", $cutter_num, $module);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    json_response(["error" => "This Cutter Number already exists for the selected Module"]);
    exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO cutters (cutter_num, cutter_module, cutter_type, lead_angle, rpm_stroke, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $cutter_num, $module, $cutter_type, $lead_angle_value, $rpm_stroke, $status, $remarks);

try {
    $stmt->execute();
    $cutter_id = (int)$stmt->insert_id;
    audit_log($conn, $user, 'Cutter Management', 'Created',
        'Created cutter ' . $cutter_num . ' (' . $status . ')', 'cutter', $cutter_id);
    http_response_code(201);
    json_response(["success" => true, "id" => $cutter_id]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        http_response_code(409);
        json_response(["error" => "This Cutter Number already exists for the selected Module"]);
    } else {
        http_response_code(500);
        json_response(["error" => "Unable to save cutter"]);
    }
} catch (Throwable $error) {
    http_response_code(500);
    json_response(["error" => "Unable to save cutter"]);
}

$stmt->close();
$conn->close();
?>
