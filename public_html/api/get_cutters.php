<?php
require __DIR__ . "/bootstrap.php";
require_auth();

$result = $conn->query("SELECT id, cutter_num, cutter_module AS module, cutter_type FROM cutters c WHERE status = 'Active' AND scrap_date IS NULL AND deleted_at IS NULL
    AND NOT EXISTS (SELECT 1 FROM cutter_service_challan_items i WHERE i.cutter_id = c.id AND i.received_at IS NULL)
    ORDER BY cutter_module, cutter_num");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
json_response($rows);
$conn->close();
?>
