<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$show_deleted = isset($_GET['trash']) && $_GET['trash'] === '1';
$status = $show_deleted ? 'Inactive' : 'Active';

$sql = "SELECT id, coupling_type, part_name, status
        FROM parts
        WHERE status = ?
        ORDER BY FIELD(coupling_type, 'GC Gear Coupling', 'NA Gear Coupling', 'Roller Chain Coupling', 'Gear', 'Sprocket'), part_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $status);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
json_response($rows);
$stmt->close();
$conn->close();
?>
