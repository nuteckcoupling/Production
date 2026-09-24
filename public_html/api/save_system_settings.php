<?php
require __DIR__ . "/bootstrap.php";
$current = require_admin();
$data = json_input();

$company_name = trim((string)($data['company_name'] ?? ''));
$report_heading = trim((string)($data['report_heading'] ?? ''));
$company_address = trim((string)($data['company_address'] ?? ''));
$idle_minutes = filter_var($data['idle_alert_minutes'] ?? null, FILTER_VALIDATE_INT);
$handover_minutes = filter_var($data['handover_overdue_minutes'] ?? null, FILTER_VALIDATE_INT);
$shift_grace = filter_var($data['shift_end_grace_minutes'] ?? null, FILTER_VALIDATE_INT);

if (strlen($company_name) < 2 || strlen($company_name) > 150) json_error('Company name must be 2-150 characters', 400);
if (strlen($report_heading) < 2 || strlen($report_heading) > 150) json_error('Report heading must be 2-150 characters', 400);
if (strlen($company_address) > 255) json_error('Company address is too long', 400);
if ($idle_minutes === false || $idle_minutes < 15 || $idle_minutes > 1440) json_error('Idle alert must be 15-1440 minutes', 400);
if ($handover_minutes === false || $handover_minutes < 5 || $handover_minutes > 1440) json_error('Handover overdue must be 5-1440 minutes', 400);
if ($shift_grace === false || $shift_grace < 0 || $shift_grace > 240) json_error('Shift-end grace must be 0-240 minutes', 400);

$address_value = $company_address === '' ? null : $company_address;
$stmt = $conn->prepare("INSERT INTO system_settings
    (id, company_name, report_heading, company_address, idle_alert_minutes,
     handover_overdue_minutes, shift_end_grace_minutes, updated_by_user_id)
    VALUES (1, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE company_name = VALUES(company_name),
      report_heading = VALUES(report_heading), company_address = VALUES(company_address),
      idle_alert_minutes = VALUES(idle_alert_minutes),
      handover_overdue_minutes = VALUES(handover_overdue_minutes),
      shift_end_grace_minutes = VALUES(shift_end_grace_minutes),
      updated_by_user_id = VALUES(updated_by_user_id)");
$stmt->bind_param('sssiiii', $company_name, $report_heading, $address_value, $idle_minutes,
    $handover_minutes, $shift_grace, $current['id']);
$stmt->execute();
$stmt->close();
audit_log($conn, $current, 'System Settings', 'Updated',
    'Updated company/report details and alert timing settings', 'system_settings', 1);
json_response(['success' => true, 'settings' => system_settings_values($conn)]);
$conn->close();
?>
