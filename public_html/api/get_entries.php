<?php
require "config.php";
require "auth.php";
require_auth();

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$date = $conn->real_escape_string($date);

$sql = "SELECT de.*, de.drg_no AS entered_drg_no, m.code AS machine_code, m.name AS machine_name,
               o.name AS operator_name, p.part_name, p.drg_no,
               c.cutter_num
        FROM daily_entries de
        JOIN machines m ON de.machine_id = m.id
        JOIN operators o ON de.operator_id = o.id
        JOIN parts p ON de.part_id = p.id
        LEFT JOIN cutters c ON de.cutter_id = c.id
        WHERE de.entry_date = '$date'
        ORDER BY de.created_at DESC";

$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$conn->close();
?>
