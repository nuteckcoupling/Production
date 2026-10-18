<?php
require __DIR__ . '/bootstrap.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    json_error('Service history is recorded automatically when a cutter is returned through its challan.', 405);
}

$cutter_id = filter_input(INPUT_GET, 'cutter_id', FILTER_VALIDATE_INT);
$period = $_GET['period'] ?? 'daily';
$value = trim((string)($_GET['value'] ?? date('Y-m-d')));
if (!$cutter_id || !in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) json_error('Invalid cutter or report period', 400);
if ($period === 'daily' || $period === 'weekly') {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) json_error('Invalid report date', 400);
    $start = clone $date;
    if ($period === 'weekly') $start->modify('monday this week');
    $end = clone $start;
    if ($period === 'weekly') $end->modify('+6 days');
    $label = $period === 'weekly' ? $start->format('d M Y') . ' - ' . $end->format('d M Y') : $start->format('d M Y');
} elseif ($period === 'monthly') {
    $date = DateTime::createFromFormat('!Y-m', $value);
    if (!$date || $date->format('Y-m') !== $value) json_error('Invalid report month', 400);
    $start = clone $date;
    $end = (clone $start)->modify('last day of this month');
    $label = $start->format('F Y');
} else {
    if (!preg_match('/^\d{4}$/', $value) || (int)$value < 1000) json_error('Invalid report year', 400);
    $start = DateTime::createFromFormat('!Y', $value);
    $end = (clone $start)->setDate((int)$value, 12, 31);
    $label = $value;
}
$start_date = $start->format('Y-m-d');
$end_date = $end->format('Y-m-d');

$stmt = $conn->prepare("SELECT c.*, (SELECT MIN(entry_date) FROM daily_entries WHERE cutter_id = c.id) AS first_used_date
    FROM cutters c WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1");
$stmt->bind_param('i', $cutter_id);
$stmt->execute();
$cutter = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cutter) json_error('Cutter not found', 404);

// shortcut: production entries have dates but no event times, so same-day output cannot be split between service events; upgrade when entries store timestamps.
$stmt = $conn->prepare("SELECT e.id, e.event_type, e.event_date, e.remarks, ch.challan_no, v.name AS vendor_name,
    COALESCE((SELECT SUM(d.total_qty) FROM daily_entries d WHERE d.cutter_id = e.cutter_id
        AND d.entry_date > COALESCE((SELECT MAX(p.event_date) FROM cutter_service_events p
            WHERE p.cutter_id = e.cutter_id AND (p.event_date < e.event_date OR (p.event_date = e.event_date AND p.id < e.id))),
            (SELECT MIN(u.entry_date) FROM daily_entries u WHERE u.cutter_id = e.cutter_id) - INTERVAL 1 DAY)
        AND d.entry_date <= e.event_date), 0) AS qty_since_previous
    FROM cutter_service_events e
    LEFT JOIN cutter_service_challan_items ci ON ci.service_event_id = e.id
    LEFT JOIN cutter_service_challans ch ON ch.id = ci.challan_id
    LEFT JOIN cutter_service_vendors v ON v.id = ch.vendor_id
    WHERE e.cutter_id = ? ORDER BY e.event_date, e.id");
$stmt->bind_param('i', $cutter_id);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT m.code AS machine_no, COALESCE(NULLIF(d.drg_no, ''), NULLIF(p.drg_no, ''), '-') AS part_no,
    p.part_name, SUM(d.total_qty) AS qty FROM daily_entries d
    JOIN machines m ON m.id = d.machine_id JOIN parts p ON p.id = d.part_id
    WHERE d.cutter_id = ? AND d.entry_date BETWEEN ? AND ?
    GROUP BY m.id, m.code, d.part_id, d.drg_no, p.drg_no, p.part_name ORDER BY m.code, p.part_name");
$stmt->bind_param('iss', $cutter_id, $start_date, $end_date);
$stmt->execute();
$monitoring_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$latest_event = $events ? $events[count($events) - 1] : null;
$cycle_start = $latest_event['event_date'] ?? $cutter['first_used_date'];
$cycle_qty = 0;
if ($cycle_start) {
    $stmt = $conn->prepare('SELECT COALESCE(SUM(total_qty), 0) AS qty FROM daily_entries WHERE cutter_id = ? AND entry_date > ?');
    $stmt->bind_param('is', $cutter_id, $cycle_start);
    $stmt->execute();
    $cycle_qty = (int)$stmt->get_result()->fetch_assoc()['qty'];
    $stmt->close();
}

json_response([
    'cutter' => $cutter,
    'is_coated' => $latest_event !== null && in_array($latest_event['event_type'], ['Coating', 'Both'], true),
    'latest_coating_date' => (function () use ($events) {
        for ($i = count($events) - 1; $i >= 0; $i--) if (in_array($events[$i]['event_type'], ['Coating', 'Both'], true)) return $events[$i]['event_date'];
        return null;
    })(),
    'monitoring' => ['period' => $period, 'value' => $value, 'label' => $label,
        'start_date' => $start_date, 'end_date' => $end_date, 'rows' => $monitoring_rows,
        'total_qty' => array_sum(array_map(static fn($row) => (int)$row['qty'], $monitoring_rows))],
    'events' => $events,
    'current_cycle' => ['from_date' => $cycle_start, 'qty' => $cycle_qty],
]);
$conn->close();
?>
