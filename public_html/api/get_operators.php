<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$result = $conn->query("SELECT id, name FROM operators WHERE status = 'Active' ORDER BY name");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
