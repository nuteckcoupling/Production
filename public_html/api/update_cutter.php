<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
$cutter_num = trim($data['cutter_num'] ?? '');
$cutter_name = trim($data['cutter_name'] ?? '');
$module = trim($data['module'] ?? '');
$cutter_type = trim($data['cutter_type'] ?? '');
$lead_angle = trim((string)($data['lead_angle'] ?? ''));
$hob_pressure_angle = trim((string)($data['hob_pressure_angle'] ?? ''));
$cutter_inner_diameter = trim((string)($data['cutter_inner_diameter'] ?? ''));
$cutter_outer_diameter = trim((string)($data['cutter_outer_diameter'] ?? ''));
$cutter_thickness = trim((string)($data['cutter_thickness'] ?? ''));
$cutter_tooth_count = trim((string)($data['cutter_tooth_count'] ?? ''));
$cutter_tooth_thickness = trim((string)($data['cutter_tooth_thickness'] ?? ''));
$purchase_date = trim((string)($data['purchase_date'] ?? ''));
$scrap_date = trim((string)($data['scrap_date'] ?? ''));
$rpm_stroke = trim($data['rpm_stroke'] ?? '');
$remarks = trim($data['remarks'] ?? '');
$status = $data['status'] ?? 'Active';
if ($scrap_date !== '') $status = 'Not Active';

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

foreach ([
    'Hob/Cutter Pressure Angle' => $hob_pressure_angle,
    'Cutter I.D.' => $cutter_inner_diameter,
    'Cutter O.D.' => $cutter_outer_diameter,
    'Cutter Thickness / Length' => $cutter_thickness,
    'Cutter Tooth Thickness' => $cutter_tooth_thickness,
] as $label => $value) {
    if ($value !== '' && (!is_numeric($value) || (float)$value <= 0 || ($label === 'Hob/Cutter Pressure Angle' && (float)$value > 90))) {
        http_response_code(400);
        json_response(['error' => $label . ' must be a positive number' . ($label === 'Hob/Cutter Pressure Angle' ? ' up to 90' : '')]);
        exit;
    }
}
$hob_pressure_angle_value = $hob_pressure_angle === '' ? null : $hob_pressure_angle;
$cutter_inner_diameter_value = $cutter_inner_diameter === '' ? null : $cutter_inner_diameter;
$cutter_outer_diameter_value = $cutter_outer_diameter === '' ? null : $cutter_outer_diameter;
$cutter_thickness_value = $cutter_thickness === '' ? null : $cutter_thickness;
$cutter_tooth_thickness_value = $cutter_tooth_thickness === '' ? null : $cutter_tooth_thickness;
if (($cutter_tooth_count !== '' && (!ctype_digit($cutter_tooth_count) || (int)$cutter_tooth_count < 1))
    || ($purchase_date !== '' && (($parsed = DateTime::createFromFormat('!Y-m-d', $purchase_date)) === false || $parsed->format('Y-m-d') !== $purchase_date))
    || ($scrap_date !== '' && (($parsed = DateTime::createFromFormat('!Y-m-d', $scrap_date)) === false || $parsed->format('Y-m-d') !== $scrap_date))) {
    http_response_code(400);
    json_response(['error' => 'Enter valid tooth count and dates']);
    exit;
}
$cutter_tooth_count_value = $cutter_tooth_count === '' ? null : (int)$cutter_tooth_count;
$purchase_date_value = $purchase_date === '' ? null : $purchase_date;
$scrap_date_value = $scrap_date === '' ? null : $scrap_date;

$check = $conn->prepare("SELECT id FROM cutters WHERE cutter_num = ? AND COALESCE(cutter_module, '') = ? AND id <> ? LIMIT 1");
$check->bind_param("ssi", $cutter_num, $module, $id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    json_response(['error' => 'This Cutter Number already exists for the selected Module']);
    exit;
}
$check->close();

$stmt = $conn->prepare("UPDATE cutters SET cutter_num = ?, cutter_name = ?, cutter_module = ?, cutter_type = ?, lead_angle = ?,
    hob_pressure_angle = ?, cutter_inner_diameter = ?, cutter_outer_diameter = ?, cutter_thickness = ?,
    cutter_tooth_count = ?, cutter_tooth_thickness = ?, purchase_date = ?, scrap_date = ?,
    rpm_stroke = ?, status = ?, remarks = ? WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("sssssssssissssssi", $cutter_num, $cutter_name, $module, $cutter_type, $lead_angle_value, $hob_pressure_angle_value, $cutter_inner_diameter_value, $cutter_outer_diameter_value, $cutter_thickness_value, $cutter_tooth_count_value, $cutter_tooth_thickness_value, $purchase_date_value, $scrap_date_value, $rpm_stroke, $status, $remarks, $id);

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
        json_response(['error' => 'This Cutter Number already exists for the selected Module']);
    } else {
        http_response_code(500);
        json_response(['error' => 'Unable to update cutter']);
    }
}
$stmt->close();
$conn->close();
?>
