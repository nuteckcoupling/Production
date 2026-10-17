<?php
require __DIR__ . '/bootstrap.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_operator_supervisor();
    $data = json_input();
    $cutter_id = filter_var($data['cutter_id'] ?? null, FILTER_VALIDATE_INT);
    $event_type = $data['event_type'] ?? '';
    $event_date = trim((string)($data['event_date'] ?? ''));
    $remarks = trim((string)($data['remarks'] ?? ''));
    $date = DateTime::createFromFormat('!Y-m-d', $event_date);
    if (!$cutter_id || !in_array($event_type, ['Coating', 'Grinding'], true)
        || !$date || $date->format('Y-m-d') !== $event_date || $event_date > date('Y-m-d')
        || strlen($remarks) > 255) {
        json_error('Enter a valid service type, date, and remarks (255 characters max)', 400);
    }
    $exists = $conn->prepare('SELECT cutter_num FROM cutters WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $exists->bind_param('i', $cutter_id);
    $exists->execute();
    $cutter = $exists->get_result()->fetch_assoc();
    $exists->close();
    if (!$cutter) json_error('Cutter not found', 404);

    $stmt = $conn->prepare('INSERT INTO cutter_service_events (cutter_id, event_type, event_date, remarks, created_by_user_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $cutter_id, $event_type, $event_date, $remarks, $user['id']);
    $stmt->execute();
    audit_log($conn, $user, 'Cutter Management', 'Service Event Added',
        $event_type . ' recorded for cutter ' . $cutter['cutter_num'] . ' on ' . $event_date,
        'cutter', $cutter_id);
    json_response(['success' => true, 'id' => (int)$stmt->insert_id]);
    $stmt->close();
    $conn->close();
    exit;
}

$cutter_id = filter_input(INPUT_GET, 'cutter_id', FILTER_VALIDATE_INT);
$report_date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$date = DateTime::createFromFormat('!Y-m-d', $report_date);
if (!$cutter_id || !$date || $date->format('Y-m-d') !== $report_date) json_error('Invalid cutter or report date', 400);

$stmt = $conn->prepare("SELECT c.*, (SELECT MIN(entry_date) FROM daily_entries WHERE cutter_id = c.id) AS first_used_date
    FROM cutters c WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1");
$stmt->bind_param('i', $cutter_id);
$stmt->execute();
$cutter = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cutter) json_error('Cutter not found', 404);

// shortcut: production entries have dates but no event times, so same-day output cannot be split between service events; upgrade when entries store timestamps.
$stmt = $conn->prepare("SELECT e.id, e.event_type, e.event_date, e.remarks,
    COALESCE((SELECT SUM(d.total_qty) FROM daily_entries d WHERE d.cutter_id = e.cutter_id
        AND d.entry_date > COALESCE((SELECT MAX(p.event_date) FROM cutter_service_events p
            WHERE p.cutter_id = e.cutter_id AND (p.event_date < e.event_date OR (p.event_date = e.event_date AND p.id < e.id))),
            (SELECT MIN(u.entry_date) FROM daily_entries u WHERE u.cutter_id = e.cutter_id) - INTERVAL 1 DAY)
        AND d.entry_date <= e.event_date), 0) AS qty_since_previous
    FROM cutter_service_events e WHERE e.cutter_id = ? ORDER BY e.event_date, e.id");
$stmt->bind_param('i', $cutter_id);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT m.code AS machine_no, COALESCE(NULLIF(d.drg_no, ''), NULLIF(p.drg_no, ''), '-') AS part_no,
    p.part_name, SUM(d.total_qty) AS qty FROM daily_entries d
    JOIN machines m ON m.id = d.machine_id JOIN parts p ON p.id = d.part_id
    WHERE d.cutter_id = ? AND d.entry_date = ?
    GROUP BY m.id, m.code, d.part_id, d.drg_no, p.drg_no, p.part_name ORDER BY m.code, p.part_name");
$stmt->bind_param('is', $cutter_id, $report_date);
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
    'is_coated' => $latest_event !== null && $latest_event['event_type'] === 'Coating',
    'latest_coating_date' => (function () use ($events) {
        for ($i = count($events) - 1; $i >= 0; $i--) if ($events[$i]['event_type'] === 'Coating') return $events[$i]['event_date'];
        return null;
    })(),
    'monitoring' => ['date' => $report_date, 'rows' => $monitoring_rows,
        'total_qty' => array_sum(array_map(static fn($row) => (int)$row['qty'], $monitoring_rows))],
    'events' => $events,
    'current_cycle' => ['from_date' => $cycle_start, 'qty' => $cycle_qty],
]);
$conn->close();
?>
