<?php
require "config.php";
require "auth.php";
require_auth();

$sql = "SELECT id, coupling_type, part_name
        FROM parts
        WHERE status = 'Active'
        ORDER BY FIELD(coupling_type, 'GC Gear Coupling', 'NA Gear Coupling', 'Roller Chain Coupling', 'Gear', 'Sprocket'), part_name";
$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$conn->close();
?>
