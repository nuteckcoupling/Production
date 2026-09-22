<?php
require "config.php";
require "auth.php";
require_operator_supervisor();

$data = json_decode(file_get_contents("php://input"), true);
$coupling_type = $data['coupling_type'] ?? '';
$part_name = strtoupper(trim($data['part_name'] ?? ''));
$allowed_types = ['GC Gear Coupling', 'NA Gear Coupling', 'Roller Chain Coupling', 'Gear', 'Sprocket'];

if (!in_array($coupling_type, $allowed_types, true)) {
    http_response_code(400);
    echo json_encode(["error" => "Select a valid Coupling Range"]);
    exit;
}

if ($part_name === '') {
    http_response_code(400);
    echo json_encode(["error" => "Coupling Code / Part Name is required"]);
    exit;
}

$check = $conn->prepare("SELECT id FROM parts WHERE part_name = ? LIMIT 1");
$check->bind_param("s", $part_name);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Coupling Code already exists"]);
    exit;
}
$check->close();

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("INSERT INTO parts (part_name, coupling_type, status) VALUES (?, ?, 'Active')");
    $stmt->bind_param("ss", $part_name, $coupling_type);
    $stmt->execute();
    $part_id = $stmt->insert_id;
    $stmt->close();

    if ($coupling_type === 'GC Gear Coupling' || $coupling_type === 'NA Gear Coupling') {
        $componentStmt = $conn->prepare("INSERT INTO part_components (part_id, component_name, sort_order) VALUES (?, ?, ?)");
        foreach ([['Hub', 1], ['Sleeve', 2]] as [$component_name, $sort_order]) {
            $componentStmt->bind_param("isi", $part_id, $component_name, $sort_order);
            $componentStmt->execute();
        }
        $componentStmt->close();
    } elseif ($coupling_type === 'Roller Chain Coupling' || $coupling_type === 'Sprocket') {
        $componentStmt = $conn->prepare("INSERT INTO part_components (part_id, component_name, sort_order) VALUES (?, 'Sprocket', 1)");
        $componentStmt->bind_param("i", $part_id);
        $componentStmt->execute();
        $componentStmt->close();
    } elseif ($coupling_type === 'Gear') {
        $componentStmt = $conn->prepare("INSERT INTO part_components (part_id, component_name, sort_order) VALUES (?, 'Gear', 1)");
        $componentStmt->bind_param("i", $part_id);
        $componentStmt->execute();
        $componentStmt->close();
    }

    $conn->commit();
    http_response_code(201);
    echo json_encode(["success" => true, "id" => $part_id]);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    if ($error->getCode() === 1062) {
        http_response_code(409);
        echo json_encode(["error" => "Coupling Code already exists"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Unable to save coupling"]);
    }
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => "Unable to save coupling"]);
}

$conn->close();
?>
