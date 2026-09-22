<?php
require "config.php";
require "auth.php";
require_auth();

$result = $conn->query("SELECT id, name FROM operators WHERE status = 'Active' ORDER BY name");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$conn->close();
?>
