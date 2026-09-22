<?php
require "config.php";
require "auth.php";
require_operator_supervisor();

$data = json_decode(file_get_contents("php://input"), true);
$cutter_num = trim($data['cutter_num'] ?? '');
$cutter_type = trim($data['cutter_type'] ?? '');
$lead_angle = trim((string)($data['lead_angle'] ?? ''));
$rpm_stroke = trim($data['rpm_stroke'] ?? '');
$remarks = trim($data['remarks'] ?? '');
$status = $data['status'] ?? 'Active';

if ($cutter_num === '') {
    http_response_code(400);
    echo json_encode(["error" => "Cutter Number is required"]);
    exit;
}

if (!in_array($status, ['Active', 'Not Active'], true)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid cutter status"]);
    exit;
}

if ($lead_angle !== '' && !is_numeric($lead_angle)) {
    http_response_code(400);
    echo json_encode(["error" => "Lead Angle must be a number"]);
    exit;
}
$lead_angle_value = $lead_angle === '' ? null : $lead_angle;

$check = $conn->prepare("SELECT id FROM cutters WHERE cutter_num = ? LIMIT 1");
$check->bind_param("s", $cutter_num);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Cutter Number already exists"]);
    exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO cutters (cutter_num, cutter_type, lead_angle, rpm_stroke, status, remarks) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $cutter_num, $cutter_type, $lead_angle_value, $rpm_stroke, $status, $remarks);

try {
    $stmt->execute();
    http_response_code(201);
    echo json_encode(["success" => true, "id" => $stmt->insert_id]);
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        http_response_code(409);
        echo json_encode(["error" => "Cutter Number already exists"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Unable to save cutter"]);
    }
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(["error" => "Unable to save cutter"]);
}

$stmt->close();
$conn->close();
?>
