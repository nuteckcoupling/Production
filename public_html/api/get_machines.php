<?php
require "config.php";
require "auth.php";
require_auth();

$result = $conn->query("SELECT id, code, name FROM machines WHERE status = 'Active' ORDER BY code");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$conn->close();
?>
