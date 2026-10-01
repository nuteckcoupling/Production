<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$show_deleted = isset($_GET['trash']) && $_GET['trash'] === '1';
$status = $show_deleted ? 'Inactive' : 'Active';

$sql = "SELECT p.id, p.coupling_type, p.part_name, p.status,
               GROUP_CONCAT(DISTINCT pc.component_name ORDER BY pc.sort_order SEPARATOR '||') AS components,
               COUNT(DISTINCT a.id) AS drawing_count
        FROM parts p
        LEFT JOIN part_components pc ON pc.part_id = p.id
        LEFT JOIN attachments a ON a.entity_type = 'part' AND a.entity_id = p.id
          AND a.category = 'Coupling Drawing'
        WHERE p.status = ?
        GROUP BY p.id, p.coupling_type, p.part_name, p.status
        ORDER BY FIELD(p.coupling_type, 'GC Gear Coupling', 'NA Gear Coupling', 'Roller Chain Coupling', 'Gear', 'Sprocket'), p.part_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $status);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['drawing_count'] = (int)$row['drawing_count'];
    $row['components'] = $row['components'] === null ? [] : explode('||', $row['components']);
    $rows[] = $row;
}
json_response($rows);
$stmt->close();
$conn->close();
?>
