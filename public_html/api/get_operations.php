<?php
require __DIR__ . "/bootstrap.php";
require_admin();

$result = $conn->query("SELECT operation FROM (
    SELECT TRIM(operation) AS operation FROM production_jobs WHERE TRIM(COALESCE(operation, '')) <> ''
    UNION
    SELECT TRIM(default_operation) AS operation FROM machines WHERE TRIM(COALESCE(default_operation, '')) <> ''
) operations ORDER BY operation");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row['operation'];
}
json_response($rows);
$conn->close();
?>
