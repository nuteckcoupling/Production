<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$result = $conn->query("SELECT id, code, name FROM machines WHERE status = 'Active' ORDER BY code");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
