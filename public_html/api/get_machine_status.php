<?php
require "config.php";
require "auth.php";
require_auth();

$machine_id = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : 0;
if ($machine_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid machine']);
    exit;
}

$stmt = $conn->prepare("SELECT
                            m.id,
                            m.code,
                            m.name,
                            COALESCE(j.status, 'Available') AS runtime_status,
                            j.id AS job_id,
                            o.name AS operator_name,
                            p.part_name
                        FROM machines m
                        LEFT JOIN production_jobs j
                          ON j.machine_id = m.id
                         AND j.status IN ('Running','Handover Pending','Breakdown','Stopped')
                        LEFT JOIN operators o ON o.id = j.current_operator_id
                        LEFT JOIN parts p ON p.id = j.part_id
                        WHERE m.id = ? AND m.status = 'Active'
                        LIMIT 1");
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Machine not found']);
    exit;
}

$row['id'] = (int)$row['id'];
$row['job_id'] = $row['job_id'] === null ? null : (int)$row['job_id'];
echo json_encode($row);

$stmt->close();
$conn->close();
?>
