<?php
require "config.php";
require "auth.php";
require_auth();

// If the same part_name has multiple rows (e.g. from different plans),
// pick the row with the HIGHEST planned_qty for that part.
$sql = "SELECT p.id, p.part_name, p.coupling_type, p.drg_no, p.planned_qty
        FROM parts p
        INNER JOIN (
            SELECT part_name, coupling_type, MAX(planned_qty) AS max_qty
            FROM parts
            WHERE status = 'Active'
            GROUP BY part_name, coupling_type
        ) m ON p.part_name = m.part_name
           AND p.coupling_type = m.coupling_type
           AND p.planned_qty = m.max_qty
        WHERE p.status = 'Active'
        GROUP BY p.part_name, p.coupling_type
        ORDER BY FIELD(p.coupling_type, 'GC Gear Coupling', 'NA Gear Coupling', 'Roller Chain Coupling', 'Gear', 'Sprocket'),
                 CASE WHEN p.coupling_type = 'Roller Chain Coupling' THEN
                   FIELD(p.part_name, 'NT1016','NT1018','NT1218','NT1222','NT1618','NT1622',
                         'NT2020','NT2418','NT2422','NT3218','NT8316','NT8312',
                         'NT6112','NT3222','NT4018','NT4022')
                 ELSE 0 END,
                 CAST(SUBSTRING_INDEX(REPLACE(p.part_name, 'HGC-', ''), '-', -1) AS UNSIGNED),
                 p.part_name";

$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$conn->close();
?>
