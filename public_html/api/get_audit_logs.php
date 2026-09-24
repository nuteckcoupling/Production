<?php
require __DIR__ . "/bootstrap.php";
require_admin();

$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$user_id = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$module = trim($_GET['module'] ?? '');
$action = trim($_GET['action'] ?? '');

foreach ([['date_from', $date_from], ['date_to', $date_to]] as [$label, $value]) {
    if ($value === '') continue;
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) json_error('Invalid audit date filter', 400);
}
if (strlen($module) > 60 || strlen($action) > 60) json_error('Invalid audit filter', 400);

$where = [];
$params = [];
$types = '';
if ($date_from !== '') { $where[] = 'created_at >= ?'; $params[] = $date_from . ' 00:00:00'; $types .= 's'; }
if ($date_to !== '') { $where[] = 'created_at <= ?'; $params[] = $date_to . ' 23:59:59'; $types .= 's'; }
if ($user_id) { $where[] = 'user_id = ?'; $params[] = $user_id; $types .= 'i'; }
if ($module !== '') { $where[] = 'module = ?'; $params[] = $module; $types .= 's'; }
if ($action !== '') { $where[] = 'action = ?'; $params[] = $action; $types .= 's'; }

$sql = "SELECT id, user_id, username, role, module, action, description, entity_type, entity_id,
               ip_address, created_at FROM audit_logs";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY id DESC LIMIT 1000';
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = $row['user_id'] === null ? null : (int)$row['user_id'];
    $row['entity_id'] = $row['entity_id'] === null ? null : (int)$row['entity_id'];
    $logs[] = $row;
}
$stmt->close();

$modules = [];
$module_result = $conn->query("SELECT DISTINCT module FROM audit_logs ORDER BY module");
while ($row = $module_result->fetch_assoc()) $modules[] = $row['module'];
$actions = [];
$action_result = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
while ($row = $action_result->fetch_assoc()) $actions[] = $row['action'];

json_response(['logs' => $logs, 'filters' => ['modules' => $modules, 'actions' => $actions]]);
$conn->close();
?>
